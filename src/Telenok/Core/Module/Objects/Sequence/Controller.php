<?php

namespace Telenok\Core\Module\Objects\Sequence;

class Controller extends \Telenok\Core\Interfaces\Presentation\TreeTabObject\Controller { 

    protected $key = 'objects-sequence';
    protected $parent = 'objects';
    protected $typeList = 'object_sequence';
    protected $presentation = 'tree-tab-object';
    protected $presentationView = 'core::module.objects-sequence.presentation';

    
    public function getAdditionalViewParam()
    {
        $this->additionalViewParam['sSearch'] = $this->getRequest()->input('sSearch');
        
        return $this->additionalViewParam;
    }   
}