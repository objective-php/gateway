<?php

namespace ObjectivePHP\Gateway;


use Aura\SqlQuery\AbstractQuery;
use Aura\SqlQuery\Common\SelectInterface;
use Aura\SqlQuery\Mysql\Insert;
use Aura\SqlQuery\Mysql\Select;
use Aura\SqlQuery\Mysql\Update;
use Aura\SqlQuery\Quoter;
use ObjectivePHP\Gateway\Entity\EntitySet;
use ObjectivePHP\Gateway\Entity\ResultSetInterface;
use ObjectivePHP\Gateway\Entity\PaginatedEntitySet;

/**
 * Class AbstractMySqlGateway
 *
 * @package Fei\ApiServer\Gateway
 */
abstract class AbstractMySqlGateway extends AbstractGateway
{

    /**
     * Link to master identifier
     */
    const READ_WRITE = 1;

    /**
     * Link to slave identifier
     */
    const READ_ONLY = 2;

    /**
     * @var \PDO
     */
    protected $readLink;

    /**
     * @var \PDO
     */
    protected $link;

    /**
     * @var \PDO
     */
    protected $lastUsedLink;


    /**
     * @return \PDO
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param \PDO $link
     *
     * @return $this
     * @throws Exception
     */
    public function setLink($link)
    {
        if (!$link instanceof \PDO) {
            throw new Exception('Link is not a PDO link', Exception::INVALID_RESOURCE);
        }

        $this->link = $link;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getReadLink()
    {
        return $this->readLink;
    }

    /**
     * @param \PDO $readLink
     *
     * @return $this
     * @throws Exception
     */
    public function setReadLink($readLink)
    {
        if (!$readLink instanceof \PDO) {
            throw new Exception('Link is not a PDO link', Exception::INVALID_RESOURCE);
        }

        $this->readLink = $readLink;

        return $this;
    }

    /**
     * @param AbstractQuery|string $query
     * @param int $link
     *
     * @return EntitySet
     * @throws Exception
     * @throws \ObjectivePHP\Gateway\Exception
     */
    public function query($query, $link = self::READ_WRITE)
    {

        $result = null;
        if ($this->shouldCache() && $this->loadFromCache($this->getQueryCacheId($query))) {
            return $this->loadFromCache($this->getQueryCacheId($query));
        }

        switch ($link) {
            case self::READ_ONLY:
                $link = $this->readLink;
                break;

            default:
            case self::READ_WRITE:
                $link = $this->link;
                break;
        }

        if (!$link instanceof \PDO) {
            throw new Exception('Selected link is not a PDO link', Exception::INVALID_RESOURCE);
        }

        $this->lastUsedLink = $link;

        $this->preparePagination($query);

        try {
            if ($query instanceof AbstractQuery) {
                $statement = $query->getStatement();
                if ($this->paginateCurrentQuery) {
                    $statement = 'SELECT SQL_CALC_FOUND_ROWS ' . substr($statement, 7);
                }

                $sth = $link->prepare($statement);
                $sth->execute($query->getBindValues());

                if ($query instanceof SelectInterface) {
                    $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);
                }
            } else {
                $rows = $link->query($query);
            }
        } catch (\PDOException $e) {
            throw new Exception(sprintf("SQL Query failed : %s - %s",
                $query, $this->getLastError()), Exception::SQL_ERROR);
        }


        if (isset($rows)) {
            $entities = $this->prepareResultSet($rows);

            if ($this->shouldCache()) {
                $this->storeInCache($this->getQueryCacheId($query), $entities);
            }

            $result = $entities;
        }

        $this->reset();

        return $result;
    }

    /**
     * @param $query
     *
     * @return string
     */
    protected function getQueryCacheId($query)
    {
        if ($query instanceof AbstractQuery) {
            return md5($query->getStatement() . serialize($query->getBindValues()));
        } else {
            return md5($query);
        }
    }

    /**
     * @param Select $query
     *
     * @return Select
     * @throws Exception
     */
    protected function preparePagination(Select $query)
    {
        // handle pagination
        if ($this->paginateNextQuery) {

            $this->paginateCurrentQuery = true;

            if (!$query instanceof Select) {
                throw new Exception('Cannot paginate string queries. Please use aura/sqlquery.');
            }


            $currentPage = $this->currentPage;
            $resultsPerPage = ($this->perPage ?: $this->defaultPerPage);

            $offset = $currentPage !== null ? ($currentPage - 1) * $resultsPerPage : null;
            $limit = $resultsPerPage;

            $query->limit($limit)->offset($offset);
        }

        return $query;
    }

    /**
     * @param null $link
     *
     * @return string
     */
    public function getLastError($link = null)
    {
        $link = $link ?: $this->lastUsedLink;

        return implode(' - ', $link->errorInfo());
    }

    public function prepareResultSet($rows): ResultSetInterface
    {
        $entities = $this->paginateCurrentQuery ? new PaginatedEntitySet() : new EntitySet();
        foreach ($rows as $row) {
            $entities[] = $this->entityFactory($row);
        }

        // inject pagination data into EntitySet
        if ($entities instanceof PaginatedEntitySet) {
            $entities->setCurrentPage($this->currentPage);
            $entities->setPerPage($this->perPage ?: $this->defaultPerPage);
            $totalQuery = "SELECT FOUND_ROWS() as total";
            $total = $this->lastUsedLink->query($totalQuery)->fetchColumn(0);
            $entities->setTotal($total);
        }

        return $entities;
    }

    protected function reset()
    {
        parent::reset();

        $this->paginateNextQuery = false;
        $this->paginateCurrentQuery = false;
        $this->currentPage = null;
        $this->perPage = null;
    }

    /**
     * @param null $link
     *
     * @return mixed
     */
    public function getLastErrorNo($link = null)
    {
        $link = $link ?: $this->lastUsedLink;

        return $link->errorCode();
    }

    /**
     * @param null $link
     *
     * @return mixed
     */
    public function getLastInsertId($link = null)
    {
        $link = $link ?: $this->lastUsedLink;

        return $link->lastInsertId();
    }

    /**
     * @param array $columns
     *
     * @return Select
     */
    protected function select(array $columns = array('*'))
    {
        $select = new Select(new Quoter("`", "`"));

        $select->cols($columns);

        return $select;
    }

    /**
     * @return Insert
     */
    protected function insert()
    {
        $insert = new Insert(new Quoter("`", "`"));

        return $insert;
    }

    /**
     * @return Update
     */
    protected function update()
    {
        $update = new Update(new Quoter("`", "`"));

        return $update;
    }


}
