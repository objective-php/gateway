<?php
/**
 * Created by PhpStorm.
 * User: gauthier
 * Date: 09/08/2017
 * Time: 14:37
 */

namespace ObjectivePHP\Gateway\Hydrator;


interface DenormalizedDataExtractorInterface
{
    public function extractDenormalized($entity);
    
    public function denormalizeData(array $data): array;
}
