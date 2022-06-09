<?php

namespace plagiarism_mcopyfind\classes;

class Document
{

    public $documentType = "DOC_TYPE_UNDEFINED";
    public $pWordHash = NULL;
    public $pSortedWordHash = NULL;
    public $pSortedWordNumber = NULL;
    public $firstHash = 0;
    public $file = null;
    public $path = null;

    public $wordNumber = 0;
    public $realwords = 0;

    function __construct($infile)
    {
        if($infile==null)
            return;
        $this->path = $infile;
    }
}
