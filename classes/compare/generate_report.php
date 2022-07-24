<?php

namespace plagiarism_mcopyfind\compare;


use DateTime;
use plagiarism_mcopyfind\compare\document;

class generate_report{

    public $settings;
    public $m_fLog; // handle for the log file
    public $m_StartTicks; //time when the comparison started
    public $m_Time; //time it took to compare the documents
    // public $m_MatchingDocumentPairs=0; //number of matching document pairs
    public $m_szSoftwareName;

    public $m_fMatch;							// handle for comparisons that exceed threshold (output)
	public $m_fMatchHtml;						// handle for comparisons that exceed threshold (output) - html
	public $m_fHtml;							// handle for output html files					
	public $m_debug;							// flag to include debug output in log file
	public $m_pQWordHash;			// a pointer to a working hash-coded word list
	public $m_pXWordHash;			// a pointer to a temporary hash-coded word list
	public $m_WordsAllocated;					// number of words allocated in the many word-related arrays
    public $m_bBriefReport=false;

    public $m_pDocS;				// All documents
    public $m_pDocL;				// Left document object
    public $m_pDocR;				// Right document object
    public $reportid;		

    public function __construct( $_reportId){
        $this->reportid = $_reportId;
        $this->wordHash = array();
        $this->wordNumber = 0;
        $this->realwords = 0;
        $this->m_CompareStep = 1000;
        if (PHP_OS_FAMILY === "Windows") {
            //$this->m_szReportFolder = "C:\\reports\\"; //path folder where the report will be saved 
            $this->m_szReportFolder = __DIR__."../../../reports/"; //path folder where the report will be saved 
            //echo "Test:";
        } elseif (PHP_OS_FAMILY === "Linux") {  
            $this->m_szReportFolder = "/var/moodledata/mcopyfind/reports/"; //path folder 
        }
        $this->szfilename="wcopy.log";									// file 
        $this->m_szSoftwareName="Mcopyfind";
        // Styles from similarity texter 
        // https://people.f4.htw-berlin.de/~weberwu/simtexter/
        $this->colors =  array(
            "#FFCDD2",
            "#a5e6ed",
            "#a7c6f2",
            "#aff2be",
            "#b8b8ff",
            "#c2f598",
            "#e6e181",
            "#e8a3ff",
            "#f29f9f",
            "#f5cf9f"
        );
        $this->SetupReport();
    }

    
    function SetupReport(){
        $this->m_StartTicks =new DateTime();
        if(!is_dir($this->m_szReportFolder)){
            mkdir($this->m_szReportFolder, 0700);
        }
        $szfilename = $this->m_szReportFolder."log.txt";
        $this->m_fLog= fopen($szfilename,"w");				// create and open log text file
        if($this->m_fLog == NULL) return "ERR_CANNOT_OPEN_LOG_FILE";
        $out ="Starting Report Files \n". $this->m_StartTicks->format($this->m_StartTicks::RSS) ."\n";
        fprintf($this->m_fLog, $out);

        $szfilename = $this->m_szReportFolder ."matches.txt";
        $this->m_fMatch= fopen($szfilename, "w");				// create and open main comparison report text file
        if($this->m_fMatch == NULL) return "ERR_CANNOT_OPEN_COMPARISON_REPORT_TXT_FILE";
    
        $szfilename=$this->m_szReportFolder. strval($this->reportid) ."matches.html";
        $this->m_fMatchHtml=fopen( $szfilename, "w");			// create and open main comparison report html file
        if($this->m_fMatchHtml == NULL) return "ERR_CANNOT_OPEN_COMPARISON_REPORT_HTML_FILE";
        
        fprintf($this->m_fMatchHtml,"<html><title>File Comparison Report</title><body><H2>File Comparison Report</H2>\n");
        fprintf($this->m_fMatchHtml,"<H3>Produced by ". $this->m_szSoftwareName ." with These Settings:</H3><br><blockquote>Shortest Phrase to Match: ".load_documents::getSettings()->m_PhraseLength ."\n");
        fprintf($this->m_fMatchHtml,"<br>Fewest Matches to Report: ".load_documents::getSettings()->m_WordThreshold."\n");
        if(load_documents::getSettings()->m_bIgnorePunctuation) fprintf($this->m_fMatchHtml,"<br>Ignore Punctuation: Yes\n");
        else fprintf($this->m_fMatchHtml,"<br>Ignore Punctuation: No\n");
        if(load_documents::getSettings()->m_bIgnoreOuterPunctuation) fprintf($this->m_fMatchHtml,"<br>Ignore Outer Punctuation: Yes\n");
        else fprintf($this->m_fMatchHtml,"<br>Ignore Outer Punctuation: No\n");
        if(load_documents::getSettings()->m_bIgnoreNumbers) fprintf($this->m_fMatchHtml,"<br>Ignore Numbers: Yes\n");
        else fprintf($this->m_fMatchHtml,"<br>Ignore Numbers: No\n");
        if(load_documents::getSettings()->m_bIgnoreCase) fprintf($this->m_fMatchHtml,"<br>Ignore Letter Case: Yes\n");
        else fprintf($this->m_fMatchHtml,"<br>Ignore Letter Case: No\n");
        if(load_documents::getSettings()->m_bSkipNonwords) fprintf($this->m_fMatchHtml,"<br>Skip Non-Words: Yes\n");
        else fprintf($this->m_fMatchHtml,"<br>Skip Non-Words: No\n");
        if(load_documents::getSettings()->m_bSkipLongWords) fprintf($this->m_fMatchHtml,"<br>Skip words longer than ".load_documents::getSettings()->m_SkipLength." characters: Yes\n");
        else fprintf($this->m_fMatchHtml,"<br>Skip Long Words: No\n");
        fprintf($this->m_fMatchHtml,"<br>Most Imperfections to Allow: \n".load_documents::getSettings()->m_MismatchTolerance);
        fprintf($this->m_fMatchHtml,"<br>Minimum %% of Matching Words: \n". load_documents::getSettings()->m_MismatchPercentage);
        fprintf($this->m_fMatchHtml,"</blockquote><br><br><table border='1' cellpadding='5'><tr><td align='center'>Perfect Match</td><td align='center'>Overall Match</td><td align='center'>View Both Files</td><td align='center'>File L</td><td align='center'>File R</td></tr>");
    }

    function ReportMatchedPair(compare_functions $compare, $docL, $docR)
    {
        $this->m_pDocL = $docL;
        $this->m_pDocR = $docR;
        $hrefL[1000]='';
        $hrefR[1000]='';					// href for the Left and Right html files
        $hrefB[1000]='';					// href from frame file for side-by-side viewing
        $dstring='';						// character buffer for document name strings
        
        //$indoc = new document($this->m_pDocL->m_filep,$this->m_pDocL->filename);			// CInputDocument class to handle inputting the document

        $this->m_pDocL->m_bBasic_Characters = load_documents::getSettings()->m_bBasic_Characters;		// inform the input document about whether we're using Basic Characters only
        $this->m_pDocR->m_bBasic_Characters = load_documents::getSettings()->m_bBasic_Characters;		// inform the input document about whether we're using Basic Characters only
        
        $iReturn = 0;
        
        // report number of matching words in the Match and Log files
        $out =$compare->m_MatchingWordsPerfect." " . $compare->m_MatchingWordsTotalL ." ". $compare->m_MatchingWordsTotalR ." ". $this->m_pDocL->filename. "  " . $this->m_pDocR->filename . "\n";
        fprintf($this->m_fMatch, $out);
        fprintf($this->m_fLog, $out);
        fflush($this->m_fLog);
    
    
        $m_szDocR = $this->m_pDocR->name; // use original naming
        $m_szDocL = $this->m_pDocL->name; // use original naming

        $hrefL = $this->m_pDocL->name;    // generate name for right html filename
        $hrefL.= "." . $this->m_pDocR->name.".html";
    
        $hrefR = $this->m_pDocR->name;	  // generate name for right html filename
        $hrefR.="." . $this->m_pDocL->name . ".html";
    
        $dstring = strval($compare->m_MatchingDocumentPairs);
        $hrefB = "SBS.".$this->m_pDocR->name . $this->m_pDocL->name . "_" . $dstring . ".html";

        // new moodle_url('/plagiarism/mcopyfind/reports/submit_all_files.php', $params);
        // $output .= html_writer::link($submiturl, get_string('submit_all_files', 'plagiarism_mcopyfind'));
        
        $szPerfectMatch =($compare->m_MatchingWordsPerfect. " (" . round(100*$compare->m_MatchingWordsPerfect/$this->m_pDocL->m_WordsTotal,2)."% L, ".  round(100*$compare->m_MatchingWordsPerfect/$this->m_pDocR->m_WordsTotal,2)."% R)");
        $szOverallMatch=($compare->m_MatchingWordsTotalL . " (". round(100*$compare->m_MatchingWordsTotalL/$this->m_pDocL->m_WordsTotal,2). "%)L; " . $compare->m_MatchingWordsTotalR . "(" . round(100*$compare->m_MatchingWordsTotalR/$this->m_pDocR->m_WordsTotal,2)."%) R");
        fprintf($this->m_fMatchHtml,
            "<tr><td>%s</td><td>%s</td><td><a href=\"%s\" target=\"_blank\">Side-by-Side</a></td><td>
            <a href=\"%s\" target=\"_blank\">%s</a></td><td><a href=\"%s\" target=\"_blank\">%s</a></td></tr>\n",
            $szPerfectMatch, $szOverallMatch, $hrefB, $hrefL, $m_szDocL, $hrefR, $m_szDocR	);
    

        $dstring = $this->m_szReportFolder .  "\\" . $hrefL;					// generate full path for left html file
        $this->m_fHtml=fopen($dstring,"w"); 				// create and open left html file
        if($this->m_fHtml == NULL) return "ERR_CANNOT_OPEN_LEFT_HTML_FILE";
    
        // create header material for left html file
    
        fprintf($this->m_fHtml,"<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\n");
        fprintf($this->m_fHtml,"<html xmlns=\"http://www.w3.org/1999/xhtml\">\n");
        fprintf($this->m_fHtml,"<head>\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\n");
        fprintf($this->m_fHtml,"<title>Comparison of ".$m_szDocL. "with". $m_szDocR ."(Matched Words =". $compare->m_MatchingWordsPerfect.")</title>\n");
        fprintf($this->m_fHtml,"<base target='right'>\n");
        fprintf($this->m_fHtml,"</head>\n");
        fprintf($this->m_fHtml,"<body>\n");
        

        // generate text body of html file, with matching words underlined
        $iReturn = generate_report::DocumentToHtml($this->m_pDocL,$compare->m_MatchMarkL,$compare->m_MatchAnchorL,$this->m_pDocL->m_WordsTotal,$hrefR); if($iReturn > -1) return $iReturn;
        
        // $this->m_pDocL->CloseDocument();					// close document
    
        fprintf($this->m_fHtml,"\n</body></html>\n");// complete html file
        fclose($this->m_fHtml); $this->m_fHtml=NULL;// close html file
    
        $dstring=$this->m_szReportFolder;			// generate full path for right html file
        $dstring .= "\\" . $hrefR;
        
        $this->m_fHtml = fopen($dstring,"w");		// create and open right html file
        if($this->m_fHtml == NULL) return "ERR_CANNOT_OPEN_RIGHT_HTML_FILE";
        
        // create header material for right html file
        
        fprintf($this->m_fHtml,"<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n");
        fprintf($this->m_fHtml,"<html xmlns=\"http://www.w3.org/1999/xhtml\">\r\n");
        fprintf($this->m_fHtml,"<head>\r\n<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />\r\n");
        fprintf($this->m_fHtml,"<title>Comparison of %s with %s (Matched Words = %d)</title>\r\n",$m_szDocR,$m_szDocL,$compare->m_MatchingWordsPerfect);
        fprintf($this->m_fHtml,"<base target='left'>\r\n");
        fprintf($this->m_fHtml,"</head>\r\n");
        fprintf($this->m_fHtml,"<body>\r\n");
        
        
        
        // $this->m_pDocR = new document($this->m_pDocR->m_filep,$this->m_pDocR->filename);
        // $indoc->m_filep = $this->m_pDocL->file;
        // $indoc->filename = $this->m_pDocL->filename;	
        //$iReturn =$indoc->definePath($this->m_pDocR->filename);
        // if($iReturn > -1)
        // {
        //     $indoc->closeDocument();							// close document
        //     return $iReturn;
        // }
        
        // generate text body of html file, with matching words underlined
        $iReturn =  generate_report::DocumentToHtml($this->m_pDocR, $compare->m_MatchMarkR,$compare->m_MatchAnchorR,$this->m_pDocR->m_WordsTotal,$hrefL); if($iReturn > -1) return $iReturn;
        
        // $this->m_pDocR->closeDocument();
    
        fprintf($this->m_fHtml,"\n</body></html>\n");	// complete html file
        fclose($this->m_fHtml); $this->m_fHtml=NULL;	// close html file
        
        $dstring = $this->m_szReportFolder;
        $dstring .= "\\" . $hrefB;
        
        $this->m_fHtml = fopen($dstring,"w");					// create and open side-by-side html file
        if($this->m_fHtml == NULL) return "ERR_CANNOT_OPEN_SIDE_BY_SIDE_HTML_FILE";
        
        // create side-by-side wrapper html file
        
        fprintf($this->m_fHtml,"<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">\r\n");
        fprintf($this->m_fHtml,"<html><title>Comparison of %s with %s (Matched Words = %d)</title>\n",$m_szDocR, $m_szDocL, $compare->m_MatchingWordsPerfect);
        fprintf($this->m_fHtml,"<frameset cols=\"*,*\" frameborder=\"YES\" border=\"1\" framespacing=\"0\">");
        fprintf($this->m_fHtml,"<frame src=\"%s\" name=\"left\">\n", $hrefL);
        fprintf($this->m_fHtml,"<frame src=\"%s\" name=\"right\">\n",$hrefR);
        fprintf($this->m_fHtml,"</frameset><body></body></html>");
    
        fclose($this->m_fHtml); $this->m_fHtml=NULL;
    
        return -1;
    }
    static function is_utf8($str) {
        return (bool) preg_match('//u', $str);
    }

    function DocumentToHtml(Document $indoc,$MatchMark, $MatchAnchor, $words, $href)
    {
        fprintf($this->m_fHtml,"<h1>%s</h1>\n",$indoc->name);
        $wordcount=0;								// current word number

        $word='';								// current word
        $tword='';								
        $DelimiterType=DEL_TYPE_WHITE;

        $xMatch=0;
        $xAnchor=0;
        $LastMatch=WORD_UNMATCHED;
        $LastAnchor=0;
        $iReturn=0;
        $cnt=0;

        for($wordcount=0;$wordcount<$words;$wordcount++)	// loop for every word
        {
            $xMatch=$MatchMark[$wordcount];
            $xAnchor=$MatchAnchor[$wordcount];
            // var_dump($MatchAnchor);
            if(($LastMatch!=$xMatch) || ($LastAnchor!=$xAnchor))	// check for a change of markup or anchor
            {
                if($LastMatch==WORD_PERFECT) fprintf($this->m_fHtml,"</font>");	// close out red markups if they were active
                else if($LastMatch==WORD_FLAW) fprintf($this->m_fHtml,"</font></i>");	// close out green italics if they were active
                else if($LastMatch==WORD_FILTERED)  fprintf($this->m_fHtml,"</font>");	// close out blue markups if they were active

                if($LastAnchor!=$xAnchor)
                {
                    if($LastAnchor>0)
                    {
                        fprintf($this->m_fHtml,"</a>");	// close out any active anchor
                        $LastAnchor=0;
                    }
                    if($xAnchor>0)
                    {
                        if(($wordcount>0) ) fprintf($this->m_fHtml,"</P>\n<P>");	// print a paragraph mark for a new line
                        //echo$MatchAnchor[$wordcount]
                        fprintf($this->m_fHtml,"<a name='%s' href='%s#%s'>",$MatchAnchor[$wordcount],$href,$MatchAnchor[$wordcount]);	// start new anchor
                    }
                }

                if($xMatch==WORD_PERFECT){
                    fprintf($this->m_fHtml,"<font  color='#000000' style=\"background-color:".($this->colors[$cnt%10])."\">");	
                    $cnt++;
                } 
                else if($xMatch==WORD_FLAW) fprintf($this->m_fHtml,"<i><font color='#007F00'>");	// start green italics for imperfection
                else if($xMatch==WORD_FILTERED)  fprintf($this->m_fHtml,"<font color='#0000FF'>");	// start blue for filtered
            }

            $LastMatch=$xMatch;
            $LastAnchor=$xAnchor;

            while(true)
            {
                if($DelimiterType == DEL_TYPE_EOF) return -1;			// shouldn't happen unless document changed during scan
                $word='';
                $iReturn = $indoc->Getword($word,$DelimiterType);		// get next word
                if($iReturn > -1) return $iReturn;	
                $tword=$word;								// copy word to a temporary

                if(load_documents::getSettings()->m_bIgnorePunctuation) Words::WordRemovePunctuation($tword);	// if ignore punctuation is active, remove punctuation
                if(load_documents::getSettings()->m_bIgnoreOuterPunctuation) Words::wordxouterpunct($tword);	// if ignore outer punctuation is active, remove outer punctuation
                if(load_documents::getSettings()->m_bIgnoreNumbers) Words::WordRemoveNumbers($tword);			// if ignore numbers is active, remove numbers
                if(load_documents::getSettings()->m_bIgnoreCase) Words::WordToLowerCase($tword);			// if ignore case is active, remove case
                if(load_documents::getSettings()->m_bSkipLongWords & (strlen($tword) > load_documents::getSettings()->m_SkipLength) ) continue;	// if skip too-long words is active, skip them
                if(load_documents::getSettings()->m_bSkipNonwords & (!Words::WordCheck($tword)) ) continue;	// if skip nonwords is active, skip them
                break;
            }
        
            // if (!generate_report::is_utf8($word))$word=utf8_encode($word);
            // for($i=0;$i<$wordLength;$i++){                    
            //     fprintf($this->m_fHtml, htmlspecialchars($word[$i])); 
            //   } //fprintf($this->m_fHtml,htmlspecialchars($word),false); // print the character, using UTF8 translation
            //for($i=0;$i<$wordLength;$i++)

            if( (!$this->m_bBriefReport) || ($xMatch == WORD_PERFECT) || ($xMatch == WORD_FLAW) || ($xMatch == WORD_FILTERED) )
            {
                // $wordLength=strlen($word);						// find length of word
                if(!$indoc->m_UTF8)$word=utf8_encode($word);
                fprintf($this->m_fHtml,htmlspecialchars($word,false));
                // print the character, using UTF8 translation
                // PrintWCharAsHtmlUTF8
                if($DelimiterType == DEL_TYPE_WHITE) fprintf($this->m_fHtml,'&nbsp;');					// print html white space
                else if($DelimiterType == DEL_TYPE_NEWLINE) fprintf($this->m_fHtml,'<br />');			// print a break for a new line
            }
        }
        if($LastMatch==WORD_PERFECT) fprintf($this->m_fHtml,"</font>");	// close out red markups if they were active
        else if($LastMatch==WORD_FLAW) fprintf($this->m_fHtml,"</font></i>");	// close out green italics if they were active
        else if($LastMatch==WORD_FILTERED)  fprintf($this->m_fHtml,"</font>");	// close out blue markups if they were active
        if($LastAnchor>0) fprintf($this->m_fHtml,"</a>");	// close out any active anchor
        return -1;
    }
    
    function FinishReports($compare)
    {
        $date =new DateTime();
        fprintf($this->m_fLog,"Finishing Report Files\n". $date->format($date::RSS) );
        fprintf($this->m_fMatchHtml,"</table>\n");
        if($compare->m_MatchingDocumentPairs == 0) fprintf($this->m_fMatchHtml,"<br>". $this->m_szSoftwareName ." found no matching pairs of documents.<br>You may want to lower the thresholds for matching and try again.<br>\n");
        else fprintf($this->m_fMatchHtml,"<br>".$this->m_szSoftwareName." found ".$compare->m_MatchingDocumentPairs." matching pairs of documents.<br>\n");
        fprintf($this->m_fMatchHtml,"</body></html>\n");
        fclose($this->m_fMatchHtml);
        $this->m_fMatchHtml=NULL;
        $this->m_Time= $date->getTimestamp() - $this->m_StartTicks->getTimestamp();

        fprintf($this->m_fLog,"\nDone. Total Time:". strval($this->m_Time) ." seconds\n");
        fclose($this->m_fLog);
        $this->m_fLog=NULL;
        return;
    }
}