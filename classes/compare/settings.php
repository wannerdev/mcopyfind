<?php

namespace plagiarism_mcopyfind\compare;

class settings{

    const WORDBUFFERLENGTH=5;
    //Settings
    public static $phraseLength = 3;
    public static $m_MismatchTolerance=2;
    public static $m_Compares=0;
    public static $m_PhraseLength = 6;
    public static $m_FilterPhraseLength = 6;
    public static $m_WordThreshold = 100;
    public static $m_SkipLength = 20;
    public static $m_MismatchPercentage = 80;
    public static $m_bBriefReport = false;
    public static $m_bIgnoreCase = false;
    public static $m_bIgnoreNumbers = false;
    public static $m_bIgnoreOuterPunctuation = false;
    public static $m_bIgnorePunctuation = false;
    public static $m_bSkipLongWords = false;
    public static $m_bSkipNonwords = false;
    public static $m_bBasic_Characters = false;
}