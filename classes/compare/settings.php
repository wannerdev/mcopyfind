<?php

namespace plagiarism_mcopyfind\compare;


 class settings{


    const WORDBUFFERLENGTH=5;
    //default Settings, absolute matching with skipping words
    public  $m_PhraseLength = 6;
    public  $m_WordThreshold = 100;
    public  $m_SkipLength = 20;
    public  $m_MismatchTolerance=0;
    public  $m_MismatchPercentage = 100;
    public  $pdfHeader = 0;
    public  $pdfFooter = 0;
    public  $m_bBriefReport = false;
    public  $m_bIgnoreCase = false;
    public  $m_bIgnoreNumbers = false;
    public  $m_bIgnoreOuterPunctuation = false;
    public  $m_bIgnorePunctuation = false;
    public  $m_bSkipLongWords = true;
    public  $m_bSkipNonwords = false;
    public  $m_bBasic_Characters = false;

    static function getdefaultSettings(){
        // log("Settings not loaded, using defaults <br>");
        return new settings(); 
    }

    function setPreset($preset){
        //Compare settings
        switch($preset){
            default :{
                return self::getdefaultSettings();
                break;
            }
            case 2:{
                $this->getMinorEditSettings();
                break;
            }
            case 3:{
                 $this->getPDFHeaderandFooterSettings();
                break;
            }
            case  4:{
                $this->getAbsoluteMatching();
                break;
            }
        }
        return $this;
    }

    function getMinorEditSettings(){
        $this->m_PhraseLength = 6;
        $this->m_WordThreshold = 80;
        $this->m_SkipLength = 20;
        $this->m_MismatchTolerance=2;
        $this->m_MismatchPercentage = 80;

        $this->m_bIgnoreNumbers = true;
        $this->m_bIgnoreCase = true;
        $this->m_bIgnoreNumbers = true;
        $this->m_bIgnoreOuterPunctuation = true;
        $this->m_bIgnorePunctuation = false;
        $this->m_bSkipLongWords = true;

        return $this;
    }


    function getPDFHeaderandFooterSettings(){
        $this->m_PhraseLength = 6;
        $this->m_WordThreshold = 100;
        $this->m_SkipLength = 20;
        $this->m_MismatchTolerance=0;
        $this->m_MismatchPercentage = 100;
        $this->pdfHeader = 20;
        $this->pdfFooter = 20;

        $this->m_bBriefReport = false;
        $this->m_bIgnoreCase = false;
        $this->m_bIgnoreNumbers = false;
        $this->m_bIgnoreOuterPunctuation = false;
        $this->m_bIgnorePunctuation = false;
        $this->m_bSkipLongWords = true;
        $this->m_bSkipNonwords = false;
        $this->m_bBasic_Characters = false;
        return $this;
    }


    function getAbsoluteMatching(){
        $this->m_PhraseLength = 6;
        $this->m_WordThreshold = 100;
        $this->m_SkipLength = 20;
        $this->m_MismatchTolerance=0;
        $this->m_MismatchPercentage = 100;
        $this->pdfHeader = 0;
        $this->pdfFooter = 0;

        $this->m_bBriefReport = false;
        $this->m_bIgnoreCase = false;
        $this->m_bIgnoreNumbers = false;
        $this->m_bIgnoreOuterPunctuation = false;
        $this->m_bIgnorePunctuation = false;
        $this->m_bSkipLongWords = false;
        $this->m_bSkipNonwords = false;
        $this->m_bBasic_Characters = false;
        return $this;
    }

   
}

