<?php

namespace plagiarism_mcopyfind\compare;

//Probably can be made static
 class settings{

    const WORDBUFFERLENGTH=5;
    //Settings
    public static $m_PhraseLength = 6;
    public static $m_WordThreshold = 100;
    public static $m_SkipLength = 0;
    public static $m_MismatchTolerance=0;
    public static $m_MismatchPercentage = 100;
    
    public static $pdfHeader = 0;
    public static $pdfFooter = 0;
    
    public static $m_bBriefReport = false;
    public static $m_bIgnoreCase = false;
    public static $m_bIgnoreNumbers = false;
    public static $m_bIgnoreOuterPunctuation = false;
    public static $m_bIgnorePunctuation = false;
    public static $m_bSkipLongWords = false;
    public static $m_bSkipNonwords = false;
    public static $m_bBasic_Characters = false;

    
    // Settings which detects 92% - 95% in default case:
    // public $m_PhraseLength = 6;
    // public $m_WordThreshold = 80;
    // public $m_SkipLength = 0;
    // public $m_MismatchTolerance=2;
    // public $m_MismatchPercentage = 80;
    
    // public $m_bBriefReport = false;
    // public $m_bIgnoreCase = false;
    // public $m_bIgnoreNumbers = false;
    // public $m_bIgnoreOuterPunctuation = false;
    // public $m_bIgnorePunctuation = false;
    // public $m_bSkipLongWords = false;
    // public $m_bSkipNonwords = false;
    // public $m_bBasic_Characters = false;

    
    // Settings which detects 93% - 97% in PDF default case:
    // public $m_PhraseLength = 6;
    // public $m_WordThreshold = 100;
    // public $m_SkipLength = 0;
    // public $m_MismatchTolerance=0;
    // public $m_MismatchPercentage = 100;
}

