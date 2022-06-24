<?php

namespace plagiarism_mcopyfind\compare;

const DEL_TYPE_NONE =0;
const DEL_TYPE_WHITE = 1;
const DEL_TYPE_NEWLINE = 2;
const DEL_TYPE_EOF = 3;

class Document
{

    public $documentType = "DOC_TYPE_UNDEFINED"; // type of document: old, new, etc.
    public $pWordHash = NULL;
    public $pSortedWordHash = NULL; // a pointer to the hash-coded word list
    public $pSortedWordNumber = NULL; // a pointer to the sorted hash-coded word list
    public $m_WordsTotal = 0; // an entry for the number of $words in the lists
    public $firstHash = 0; // an entry for the first word with more than 3 chars

    public $file = null;
    public $path = null;
    public $wordNumber = 0;
    public $realwords = 0;

    public function __construct($infile)
    {
        if($infile==null)
            return;
        $this->path = $infile;
    }

    static function documentToHtml($indoc,  $MatchMark, $MatchAnchor,  $words, $href)
    {
        $m_fHtml= fopen($indoc->file.".html", "w");				// create and open main comparison report text file
        $wordcount=0;								// current word number

        $word[256]="";
        $tword[256]="";
        $DelimiterType=DEL_TYPE_WHITE;

        $xMatch="";
        $xAnchor="";

        $LastMatch=WORD_UNMATCHED;
        $LastAnchor=0;

        $iReturn=0;

        for($wordcount=0;$wordcount<$words;$wordcount++)	// loop for every word
        {
            $xMatch=$MatchMark[$wordcount];
            $xAnchor=$MatchAnchor[$wordcount];

            if(($LastMatch!=$xMatch) || ($LastAnchor!=$xAnchor))	// check for a change of markup or anchor
            {
                if($LastMatch==WORD_PERFECT) fprintf($m_fHtml,"</font>");	// close out red markups if they were active
                else if($LastMatch==WORD_FLAW) fprintf($m_fHtml,"</font></i>");	// close out green italics if they were active
                else if($LastMatch==WORD_FILTERED)  fprintf($m_fHtml,"</font>");	// close out blue markups if they were active

                if($LastAnchor!=$xAnchor)
                {
                    if($LastAnchor>0)
                    {
                        fprintf($m_fHtml,"</a>");	// close out any active anchor
                        $LastAnchor=0;
                    }
                    if($xAnchor>0)
                    {
                        if(settings::$m_bBriefReport && ($wordcount>0) ) fprintf($m_fHtml,"</P>\n<P>");	// pr$a paragraph mark for a new line
                        fprintf($m_fHtml,"<a name='%i' $href='%s#%i'>",$MatchAnchor[$wordcount],$href,$MatchAnchor[$wordcount]);	// start new anchor
                    }
                }

                if($xMatch==WORD_PERFECT) fprintf($m_fHtml,"<font color='#FF0000'>");	// start red for perfection
                else if($xMatch==WORD_FLAW) fprintf($m_fHtml,"<i><font color='#007F00'>");	// start green italics for imperfection
                else if($xMatch==WORD_FILTERED)  fprintf($m_fHtml,"<font color='#0000FF'>");	// start blue for filtered
            }

            $LastMatch=$xMatch;
            $LastAnchor=$xAnchor;

            while(true)
            {
                if($DelimiterType == DEL_TYPE_EOF) return -1;			// shouldn't happen unless document changed during scan
                //$iReturn = $indoc.GetWord($word,$DelimiterType); if($iReturn > -1) return $iReturn;	// get next word
                $iReturn = fgetcsv($indoc, 0, ' '); //if($iReturn > -1) return $iReturn;	// get next word

                // wcscpy_s($tword,$word);								// copy word to a temporary

                // if(settings::$m_bIgnorePunctuation) WordRemovePunctuation($tword);	// if ignore punctuation is active, remove punctuation
                // if(settings::$m_bIgnoreOuterPunctuation) wordxouterpunct($tword);	// if ignore outer punctuation is active, remove outer punctuation
                // if(settings::$m_bIgnoreNumbers) WordRemoveNumbers($tword);			// if ignore numbers is active, remove numbers
                // if(settings::$m_bIgnoreCase) WordToLowerCase($tword);			// if ignore case is active, remove case
                // if(settings::$m_bSkipLongWords & (wcslen($tword) > settings::$m_SkipLength) ) continue;	// if skip too-long $words is active, skip them
                // if(settings::$m_bSkipNonwords & (!WordCheck($tword)) ) continue;	// if skip non$words is active, skip them

                break;
            }
        
            if( (!settings::$m_bBriefReport) || ($xMatch == WORD_PERFECT) || ($xMatch == WORD_FLAW) )
            {
                // $wordLength=wcslen($word);						// find length of word
                // for($i=0;$i<$wordLength;$i++) PrintWCharAsHtmlUTF8($m_fHtml,$word[i]);			// pr$the character, using UTF8 translation
                if($DelimiterType == DEL_TYPE_WHITE) fprintf($m_fHtml," ");					// pr$a blank for white space
                else if($DelimiterType == DEL_TYPE_NEWLINE) fprintf($m_fHtml,"<br>");			// pr$a break for a new line
            }
        }
        if($LastMatch==WORD_PERFECT) fprintf($m_fHtml,"</font>");	// close out red markups if they were active
        else if($LastMatch==WORD_FLAW) fprintf($m_fHtml,"</font></i>");	// close out green italics if they were active
        else if($LastMatch==WORD_FILTERED)  fprintf($m_fHtml,"</font>");	// close out blue markups if they were active
        if($LastAnchor>0) fprintf($m_fHtml,"</a>");	// close out any active anchor
        return ;
    }
}
