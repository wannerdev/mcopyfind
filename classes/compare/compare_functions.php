<?php

namespace plagiarism_mcopyfind\compare;

use SplFixedArray;

const WORD_UNMATCHED=-1;
const WORD_PERFECT=0;
const WORD_FLAW=1;
const WORD_FILTERED=2;


function PercentMatching($firstL,$firstR,$lastL,$lastR,$PerfectMatchingWords){
    return (200*$PerfectMatchingWords)/($lastL-$firstL+$lastR-$firstR+2);
}

class compare_functions{

    public $settings;
    public $m_Documents; //number of documents
    public $m_Compares=0; //number of comparisons
    public $m_pDocs; //array of documents
    public $m_MatchingDocumentPairs=0;    
	public $m_MatchingWordsPerfect=0;
    
    
	public $m_MatchMarkL; 
    public $m_MatchMarkR; 		// left and right matched word markup list pointers

	public $m_MatchAnchorL;
    public $m_MatchAnchorR;	// left and right matched string anchors for html files

	public $m_MatchMarkTempL; 
    public $m_MatchMarkTempR;// left and right matched word  markup list pointers - temporary

    public $m_TotalCompares=0;
    public $m_CompareStep;

    public $reportGen;

    function __construct( $docs, $_reportId){

        $this->m_pDocs = $docs;
        $this->reportGen = new generate_report( $_reportId);
        $this->m_Documents = count($docs);
        $this->m_CompareStep=1000;
        // $this->settings= $_settings;
        $this->m_MatchingWordsPerfect=0;
        $this->m_MatchMarkL=new SplFixedArray(10000);
        $this->m_MatchMarkR=new SplFixedArray(10000);		// left and right matched word markup list pointers
        $this->m_MatchAnchorL=new SplFixedArray(10000);
        $this->m_MatchAnchorR=new SplFixedArray(10000);	// left and right matched string anchors for html files
        $this->m_MatchMarkTempL=new SplFixedArray(10000); //todo change to wordsize
        $this->m_MatchMarkTempR=new SplFixedArray(10000);// left and right matched word  markup list pointers - temporary
    }

    function ComparePair(Document $docL,Document $docR)
    {
        echo ("Comparing ".$docL->filename." and ".$docR->filename."\n <br>" );
        fprintf($this->reportGen->m_fLog, "Comparing ".$docL->filename." and ".$docR->filename."\n");
        $wordNumberL=0;
        $wordNumberR=0;						// word number for left document and right document
        $WordNumberRedundantL=0;
        $WordNumberRedundantR=0;		// word number of end of redundant words
        $counterL=0;
        $counterR=0;						// word number counter, for loops
        $firstL=0;$firstR=0;									// first matching word in left document and right document
        $lastL='';$lastR='';									// last matching word in left document and right document
        $firstLp='';$firstRp='';								// first perfectly matching word in left document and right document
        $lastLp='';$lastRp='';									// last perfectlymatching word in left document and right document
        $firstLx='';$firstRx='';								// first original perfectly matching word in left document and right document
        $lastLx='';$lastRx='';									// last original perfectlymatching word in left document and right document
        $flaws=0;											// flaw count
        $hash=0;                                            // hash value for word							
        $anchor=0;											// number of current match anchor

        $MatchingWordsPerfect=0;

        $this->m_MatchingWordsPerfect=0;// count of perfect matches within a single phrase
        $this->m_MatchingWordsTotalL=0;
        $this->m_MatchingWordsTotalR=0;

        for($wordNumberL=0;$wordNumberL<$docL->m_WordsTotal;$wordNumberL++)	// loop for all left words
        {
            $this->m_MatchMarkL[$wordNumberL]=WORD_UNMATCHED;		// set the left match markers to "WORD_UNMATCHED"
            $this->m_MatchAnchorL[$wordNumberL]=0;					// zero the left match anchors
        }
        for($wordNumberR=0;$wordNumberR<$docR->m_WordsTotal;$wordNumberR++)	// loop for all right words
        {
            $this->m_MatchMarkR[$wordNumberR]=WORD_UNMATCHED;		// set the right match markers to "WORD_UNMATCHED"
            $this->m_MatchAnchorR[$wordNumberR]=0;					// zero the right match anchors
        }

        //
        // filter left document
        //
        $wordNumberL=$docL->firstHash;						// start left at first >3 letter word 
        $wordNumberR=$docR->firstHash;						// start right at first >3 letter word$m
        $anchor=0;											// start with no html anchors assigned
        
        while ( ($wordNumberL < $docL->m_WordsTotal)			// loop while there are still words to check
                && ($wordNumberR < $docR->m_WordsTotal) )
        {
            // if the next word in the left sorted hash-coded list has been matched
            if( $this->m_MatchMarkL[$docL->pSortedWordNumber[$wordNumberL]] != WORD_UNMATCHED )
            {
                $wordNumberL++;								// advance to next left sorted hash-coded word
                continue;
            }

            // echo "+++++++++++\n INDEX:".$index;
            // if the next word in the right sorted hash-coded list has been matched
            if( $this->m_MatchMarkR[$docR->pSortedWordNumber[$wordNumberR]] != WORD_UNMATCHED )
            {
                $wordNumberR++;								// skip to next right sorted hash-coded word
                continue;
            }

            // check for left word less than right word
            if( $docL->pSortedWordHash[$wordNumberL] < $docR->pSortedWordHash[$wordNumberR] )
            {
                $wordNumberL++;								// advance to next left word
                if ( $wordNumberL >= $docL->m_WordsTotal) break;
                continue;									// and resume looping
            }

            // check for right word less than left word
            if( $docL->pSortedWordHash[$wordNumberL] > $docR->pSortedWordHash[$wordNumberR] )
            {
                $wordNumberR++;								// advance to next right word
                if ( $wordNumberR >= $docR->m_WordsTotal) break;
                continue;									// and resume looping
            }

            // we have a match, so check redundancy of this words and compare all copies of this word
            $hash=$docL->pSortedWordHash[$wordNumberL];
            $WordNumberRedundantL=$wordNumberL;
            $WordNumberRedundantR=$wordNumberR;
            while($WordNumberRedundantL < ($docL->m_WordsTotal - 1))
            {
                if( $docL->pSortedWordHash[$WordNumberRedundantL + 1] == $hash ) $WordNumberRedundantL++;
                else break;
            }

            while($WordNumberRedundantR < ($docR->m_WordsTotal - 1))
            {
                if( $docR->pSortedWordHash[$WordNumberRedundantR + 1] == $hash ) $WordNumberRedundantR++;
                else break;
            }

            for($counterL=$wordNumberL;$counterL<=$WordNumberRedundantL;$counterL++)	// loop for each copy of this word on the left
            {
                if( $this->m_MatchMarkL[$docL->pSortedWordNumber[$counterL]] != WORD_UNMATCHED ) continue;	// skip words that have been matched already
                for($counterR=$wordNumberR;$counterR<=$WordNumberRedundantR;$counterR++)	// loop for each copy of this word on the right
                {
                    if($this->m_MatchMarkR[$docR->pSortedWordNumber[$counterR]] != WORD_UNMATCHED ) continue;	//   skip words that have been matched already

                    // look up and down the hash-coded (not sorted) lists for matches
                    $this->m_MatchMarkTempL[$docL->pSortedWordNumber[$counterL]]=WORD_PERFECT;	// markup word in temporary list at perfection quality
                    $this->m_MatchMarkTempR[$docR->pSortedWordNumber[$counterR]]=WORD_PERFECT;	// markup word in temporary list at perfection quality

                    $firstL = $docL->pSortedWordNumber[$counterL]-1;	// start left just before current word
                    $lastL  = $docL->pSortedWordNumber[$counterL]+1;	// end left just after current word
                    $firstR = $docR->pSortedWordNumber[$counterR]-1;  // start right just before current word
                    $lastR  = $docR->pSortedWordNumber[$counterR]+1;   	// end right just after current word

                    while( ($firstL >= 0) && ($firstR >= 0) )		// if we aren't at the start of either document,
                    {

                        // Note: when we leave this loop, $firstL and $firstR will always point one word before the first match
                        
                        // make sure that left and right words haven't been used in a match before and
                        // that the two words actually match. If so, move up another word and repeat the test.

                        if( $this->m_MatchMarkL[$firstL] != WORD_UNMATCHED ) break;
                        if( $this->m_MatchMarkR[$firstR] != WORD_UNMATCHED ) break;

                        if( $docL->m_pWordHash[$firstL] == $docR->m_pWordHash[$firstR] )
                        {
                            $this->m_MatchMarkTempL[$firstL]=WORD_PERFECT;		// markup word in temporary list
                            $this->m_MatchMarkTempR[$firstR]=WORD_PERFECT;		// markup word in temporary list
                            $firstL--;									// move up on left
                            $firstR--;									// move up on right
                            continue;
                        }
                        break;
                    }

                    while( ($lastL < $docL->m_WordsTotal) && ($lastR < $docR->m_WordsTotal) ) {// if we aren't at the end of either document
                
                        // Note: when we leave this loop, $lastL and $lastR will always point one word after last match

                        // make sure that left and right words haven't been used in a match before and
                        // that the two words actually match. If so, move up another word and repeat the test.
                        if( $this->m_MatchMarkL[$lastL] != WORD_UNMATCHED ) break;
                        if( $this->m_MatchMarkR[$lastR] != WORD_UNMATCHED ) break;
                        if( $docL->m_pWordHash[$lastL] == $docR->m_pWordHash[$lastR] )
                        {
                            $this->m_MatchMarkTempL[$lastL]=WORD_PERFECT;	// markup word in temporary list
                            $this->m_MatchMarkTempR[$lastR]=WORD_PERFECT;	// markup word in temporary list
                            $lastL++;								// move down on left
                            $lastR++;								// move down on right
                            continue;
                        }				
                        break;
                    }

                    $firstLp=$firstL+1;						// pointer to first perfect match left
                    $firstRp=$firstR+1;						// pointer to first perfect match right
                    $lastLp=$lastL-1;							// pointer to last perfect match left
                    $lastRp=$lastR-1;							// pointer to last perfect match right
                    $MatchingWordsPerfect=$lastLp-$firstLp+1;	// save number of perfect matche;
                    if(load_documents::getSettings()->m_MismatchTolerance > 0)				// are we accepting imperfect matches?
                    {

                        $firstLx=$firstLp;					// save pointer to word before first perfect match left
                        $firstRx=$firstRp;					// save pointer to word before first perfect match right
                        $lastLx=$lastLp;						// save pointer to word after last perfect match left
                        $lastRx=$lastRp;						// save pointer to word after last perfect match right

                        $flaws=0;							// start with zero $flaws
                        if( ($firstL >= 0) && ($firstR >= 0) )		// if we n't at the start of either document,
                        {

                            // Note: when we leave this loop, $firstL and $firstR will always point one word before the first reportable match
                            
                            // make sure that left and right words haven't been used in a match before and
                            // that the two words actually match. If so, move up another word and repeat the test.
                            if( $this->m_MatchMarkL[$firstL] != WORD_UNMATCHED ) break;
                            if( $this->m_MatchMarkR[$firstR] != WORD_UNMATCHED ) break;
                            if( $docL->m_pWordHash[$firstL] == $docR->m_pWordHash[$firstR] )
                            {
                                $MatchingWordsPerfect++;				// increment perfect match count;
                                $flaws=0;							// having just found a perfect match, we're back to perfect matching
                                $this->m_MatchMarkTempL[$firstL]=WORD_PERFECT;			// markup word in temporary list
                                $this->m_MatchMarkTempR[$firstR]=WORD_PERFECT;			// markup word in temporary list
                                $firstLp=$firstL;						// save pointer to first left perfect match
                                $firstRp=$firstR;						// save pointer to first right perfect match
                                $firstL--;							// move up on left
                                $firstR--;							// move up on right
                                continue;
                            }

                            // we're at a flaw, so increase the flaw count
                            $flaws++;
                            if( $flaws > load_documents::getSettings()->m_MismatchTolerance ) break;	// check for maximum $flaws reached

                            if( ($firstL-1) >= 0 )					// check one word earlier on left (if it exists)
                            {
                                if( $this->m_MatchMarkL[$firstL-1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
                                
                                if( $docL->m_pWordHash[$firstL-1] == $docR->m_pWordHash[$firstR] )
                                {
                                    if( PercentMatching($firstL-1,$firstR,$lastLx,$lastRx,$MatchingWordsPerfect+1) < load_documents::getSettings()->m_MismatchPercentage ) break;	// are we getting too imperfect?
                                    $this->m_MatchMarkTempL[$firstL]=WORD_FLAW;	// markup non-matching word in left temporary list
                                    $firstL--;						// move up on left to skip over the flaw
                                    $MatchingWordsPerfect++;			// increment perfect match count;
                                    $flaws=0;						// having just found a perfect match, we're back to perfect matching
                                    $this->m_MatchMarkTempL[$firstL]=WORD_PERFECT;		// markup word in left temporary list
                                    $this->m_MatchMarkTempR[$firstR]=WORD_PERFECT;		// markup word in right temporary list
                                    $firstLp=$firstL;					// save pointer to first left perfect match
                                    $firstRp=$firstR;					// save pointer to first right perfect match
                                    $firstL--;						// move up on left
                                    $firstR--;						// move up on right
                                    continue;
                                }
                            }

                            if( ($firstR-1) >= 0 )					// check one word earlier on right (if it exists)
                            {
                                if( $this->m_MatchMarkR[$firstR-1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word

                                if( $docL->m_pWordHash[$firstL] == $docR->m_pWordHash[$firstR-1] )
                                {
                                    if( PercentMatching($firstL,$firstR-1,$lastLx,$lastRx,$MatchingWordsPerfect+1) < load_documents::getSettings()->m_MismatchPercentage ) break;	// are we getting too imperfect?
                                    $this->m_MatchMarkTempR[$firstR]=WORD_FLAW;	// markup non-matching word in right temporary list
                                    $firstR--;						// move up on right to skip over the flaw
                                    $MatchingWordsPerfect++;			// increment perfect match count;
                                    $flaws=0;						// having just found a perfect match, we're back to perfect matching
                                    $this->m_MatchMarkTempL[$firstL]=WORD_PERFECT;		// markup word in left temporary list
                                    $this->m_MatchMarkTempR[$firstR]=WORD_PERFECT;		// markup word in right temporary list
                                    $firstLp=$firstL;					// save pointer to first left perfect match
                                    $firstRp=$firstR;					// save pointer to first right perfect match
                                    $firstL--;						// move up on left
                                    $firstR--;						// move up on right
                                    continue;
                                }
                            }

                            if( PercentMatching($firstL-1,$firstR-1,$lastLx,$lastRx,$MatchingWordsPerfect) < load_documents::getSettings()->m_MismatchPercentage ) break;	// are we getting too imperfect?
                            $this->m_MatchMarkTempL[$firstL]=WORD_FLAW;		// markup word in left temporary list
                            $this->m_MatchMarkTempR[$firstR]=WORD_FLAW;		// markup word in right temporary list
                            $firstL--;								// move up on left
                            $firstR--;								// move up on right
                        }
            
                        $flaws=0;							// start with zero $flaws
                        while( ($lastL < $docL->m_WordsTotal) && ($lastR < $docR->m_WordsTotal) )
                        { // if we aren't at the end of either document
                    
                            // Note: when we leave this loop, $lastL and $lastR will always point one word after last match
                            // make sure that left and right words haven't been used in a match before and
                            // that the two words actually match. If so, move up another word and repeat the test.
                            if( $this->m_MatchMarkL[$lastL] != WORD_UNMATCHED ) break;
                            if( $this->m_MatchMarkR[$lastR] != WORD_UNMATCHED ) break;
                            if( $docL->m_pWordHash[$lastL] == $docR->m_pWordHash[$lastR] )
                            {
                                $MatchingWordsPerfect++;				// increment perfect match count;
                                $flaws=0;							// having just found a perfect match, we're back to perfect matching;							this->m_MatchMarkTempL[$lastL]=WORD_PERFECT;	// markup word in temporary list
                                $this->m_MatchMarkTempL[$lastL]=WORD_PERFECT;	// markup word in temporary list
                                $this->m_MatchMarkTempR[$lastR]=WORD_PERFECT;	// markup word in temporary list
                                $lastLp=$lastL;						// save pointer to last left perfect match
                                $lastRp=$lastR;						// save pointer to last right perfect match
                                $lastL++;							// move down on left
                                $lastR++;;						// move down on right
                                continue;
                            }
                            $flaws++;
                            fprintf($this->reportGen->m_fLog,'Flaw found ' . $flaws   . "\n");
                            if( $flaws == load_documents::getSettings()->m_MismatchTolerance ) break;	// check for maximum $flaws reached

                            if( ($lastL+1) < $docL->m_WordsTotal )		// check one word later on left (if it exists)
                            {
                                if( $this->m_MatchMarkL[$lastL+1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
                                
                                if( $docL->m_pWordHash[$lastL+1] == $docR->m_pWordHash[$lastR] )
                                {
                                    if( PercentMatching($firstLx,$firstRx,$lastL+1,$lastR,$MatchingWordsPerfect+1) < load_documents::getSettings()->m_MismatchPercentage ) break;	// are we getting too imperfect?
                                        $this->m_MatchMarkTempL[$lastL]=WORD_FLAW;		// marku; non-matching word in left temporary list
                                        $lastL++;						// move down on;left to skip over the flaw
                                        $MatchingWordsPerfect++;			// increment perfect match count;
                                        $flaws=0;						// having just ;ound a perfect match, we're back to perfect matching
                                        $this->m_MatchMarkTempL[$lastL]=WORD_PERFECT;	// markup word in lefttemporary list
                                        $this->m_MatchMarkTempR[$lastR]=WORD_PERFECT;	// markup word in right temporary list
                                        $lastLp=$lastL;					// save pointer to last left perfect match
                                        $lastRp=$lastR;					// save pointer to last right perfect match
                                        $lastL++;						// move down on left
                                        $lastR++;;					// move down on right
                                        continue;
                                }
                            }
                            if( ($lastR+1) < $docR->m_WordsTotal )	// check one word later on right (if it exists)
                            {
                                if( $this->m_MatchMarkR[$lastR+1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
                                if( $docL->m_pWordHash[$lastL] == $docR->m_pWordHash[$lastR+1] )
                                {
                                    if( PercentMatching($firstLx,$firstRx,$lastL,$lastR+1,$MatchingWordsPerfect+1) < load_documents::getSettings()->m_MismatchPercentage ) break;	// are we getting too imperfect?
                                        $this->m_MatchMarkTempR[$lastR]=WORD_FLAW;		// mar;up non-matching word in right temporary list
                                        $lastR++;						// move down ;n right to skip over the flaw
                                        $MatchingWordsPerfect++;			// increment perfect match count;
                                        $flaws=0;						// having jus; found a perfect match, we're back to perfect matching
                                        $this->m_MatchMarkTempL[$lastL]=WORD_PERFECT;	// markup word in left temporary list
                                        $this->m_MatchMarkTempR[$lastR]=WORD_PERFECT;	// markup word in right temporary list
                                        $lastLp=$lastL;					// save pointer to last left perfect match
                                        $lastRp=$lastR;					// save pointer to last right perfect match
                                        $lastL++;						// move down on left
                                        $lastR++;;					// move down on right
                                        continue;
                            
                                }
                            }
                            if( PercentMatching($firstLx,$firstRx,$lastL+1,$lastR+1,$MatchingWordsPerfect) < load_documents::getSettings()->m_MismatchPercentage ) break;	// are we getting too imperfect?
                            $this->m_MatchMarkTempL[$lastL]=WORD_FLAW;		// marku; word in left temporary list
                            $this->m_MatchMarkTempR[$lastR]=WORD_FLAW;		// mark;p word in right temporary list
                            $lastL++;								// move down on left
                            $lastR++;								// move;down on right
                        }				
                    }
                    if( $MatchingWordsPerfect >= load_documents::getSettings()->m_PhraseLength )	// check that phrase has enough perfect matches in it to mark
                    {
                        $anchor++;									// increment anchor count
                        for($i=$firstLp;$i<=$lastLp;$i++)				// loop for all left matched words
                        {
                            $this->m_MatchMarkL[$i]=$this->m_MatchMarkTempL[$i];	// copy over left matching markup
                            if($this->m_MatchMarkTempL[$i]==WORD_PERFECT) $this->m_MatchingWordsPerfect++;	// count the number of perfect matching words (same as for right document)
                            $this->m_MatchAnchorL[$i]=$anchor;				// identify the anchor for this phrase
                        }
                        $this->m_MatchingWordsTotalL += $lastLp-$firstLp+1;	// add the number of words in the matching phrase, whether perfect or flawed matches
                        for($i=$firstRp;$i<=$lastRp;$i++)				// loop for all right matched words
                        {
                            $this->m_MatchMarkR[$i]=$this->m_MatchMarkTempR[$i];	// copy over right matching markup
                            $this->m_MatchAnchorR[$i]=$anchor;				// identify the anchor for this phrase
                        }
                        $this->m_MatchingWordsTotalR += $lastRp-$firstRp+1;	// add the number of words in the matching phrase, whether perfect or flawed matches
                    }
                }
            }
            $wordNumberL=$WordNumberRedundantL + 1;			// continue searching after the last redundant word on left
            $wordNumberR=$WordNumberRedundantR + 1;			// continue searching after the last redundant word on right
        }

        $this->m_Compares++;										// increment count of comparisons
        if( ($this->m_Compares % $this->m_CompareStep)	== 0 )				// if count is divisible by 1000,
        {
            fprintf($this->reportGen->m_fLog, "Comparing: ".$this->m_Compares." of ".$this->m_TotalCompares ."\n");
            // fwprintf(m_fLog,L"Comparing Documents, %d Completed\n",m_Compares);
            // fflush(m_fLog);
        }
        return -1;
    }


    function SetupProgressReports($Group1,$Group2,$Group3){
        $Group1Count=0;
        $Group2Count=0;
        $Group3Count=0;
        
        for($i =0; $i< $this->m_Documents; $i++ )  	
        {
            if($this->m_pDocs[$i]->m_DocumentType== $Group1) $Group1Count++;
            if($this->m_pDocs[$i]->m_DocumentType== $Group2) $Group2Count++;
            if($this->m_pDocs[$i]->m_DocumentType== $Group3) $Group3Count++;
        }
        if($Group1 == DOC_TYPE_UNDEFINED) $Group1Count=0;	// ignore this group
        if($Group2 == DOC_TYPE_UNDEFINED) $Group2Count=0;	// ignore this group
        if($Group3 == DOC_TYPE_UNDEFINED) $Group3Count=0;	// ignore this group
    
        $this->m_TotalCompares=($Group1Count * $Group2Count) + (($Group3Count * ($Group3Count-1))/2);
    
        if($this->m_TotalCompares<100) $this->m_CompareStep=1;
        else if($this->m_TotalCompares<1000) $this->m_CompareStep=10;
        else if($this->m_TotalCompares<10000) $this->m_CompareStep=100;
        else $this->m_CompareStep=1000;
    }


    function RunComparison() {
        $load = new load_documents();
        // $DocL;$DocR;			// left document and right document
        // $szMessage;			// status messages
        // $i;					// local index counter
        // $irvalue;
        $this->m_MatchingWordsTotalL=0;		// total number of matching words in left document
        $this->m_MatchingWordsTotalR=0;        // total number of matching words in right document 
        $g_abort = false;					    		// abort signal when true
        
               
        fprintf($this->reportGen->m_fLog,"Starting to Load and Hash-Code Documents\n");					// log loading step
        foreach ($this->m_pDocs as $doc) {
            $load->loadDocument($doc);
        }

        fprintf($this->reportGen->m_fLog,"Done Loading Documents\n\n");		// Finish loading step log
        fprintf($this->reportGen->m_fLog,"Starting to Compare Documents\n");		// Finish loading step log


	    $this->SetupProgressReports(DOC_TYPE_OLD,DOC_TYPE_NEW,DOC_TYPE_NEW);
        for($i =0; $i< $this->m_Documents; $i++ )  			// for all possible left documents
        {	
            $DocL = $this->m_pDocs[$i];			
            $DocL->OpenDocument();				// get left document
            for($j =$i; $j< $this->m_Documents; $j++ )  			// for all possible right documents
            {
                $DocR = $this->m_pDocs[$j];
                $DocR->OpenDocument();	
                if($DocL == $DocR ) continue;				// skip if same document

                if($g_abort){
                    // m_pStatus->SetWindowTextW(L"Comparison Aborted");
                    return "ERR_ABORT";
                }
                //old document means additional document (e.g. standard solutions)
                if( ($DocL->m_DocumentType == DOC_TYPE_OLD) && ($DocR->m_DocumentType == DOC_TYPE_OLD) ) continue;	// don't compare an old document with an old document

                $irvalue = $this->ComparePair($DocL,$DocR); if($irvalue > -1) return $irvalue;			// compare the two documents
                
                if( ($this->m_Compares %$this->m_CompareStep)	== 0 )				// if count is divisible by 1000,
                {
                    fprintf($this->reportGen->m_fLog,"Comparing Documents,". $this->m_Compares . " Completed\n");		// step log
                    // $szMessage.Format(L"Comparing Documents, %d Completed",m_pDoc->m_Compares);
                    // $m_pStatus->SetWindowTextW(szMessage);
                    // $m_pProgress->SetPos(int((100.0*double(m_pDoc->m_Compares))/double(m_pDoc->m_TotalCompares)));
                }
                
                if($this->m_MatchingWordsPerfect>=load_documents::getSettings()->m_WordThreshold)		// if there are enough matches to report,
                {
                    $this->m_MatchingDocumentPairs++;				// increment count of matched pairs of documents
                    $this->reportGen->ReportMatchedPair($this,$DocL,$DocR);

                    //m_report is simply a list-window where we can see the matches of the comparison
                    // $nItem=$m_pReport->GetItemCount();

                    $szPerfectMatch= $this->m_MatchingWordsPerfect. "(" . round(100*$this->m_MatchingWordsPerfect/$DocL->m_WordsTotal,2) . "L," . round(100*$this->m_MatchingWordsPerfect/$DocR->m_WordsTotal,2) . "R)"; 
                
                    $szOverallMatch= $this->m_MatchingWordsTotalL . "(". round(100*$this->m_MatchingWordsTotalL/$DocL->m_WordsTotal,2) . "%%)L;" . "," . $this->m_MatchingWordsTotalR . "(". round(100*$this->m_MatchingWordsTotalR/$DocR->m_WordsTotal,2) . "%%)R";
                    //echo("Item:" . $szPerfectMatch . " ". $szOverallMatch . " " . $this->m_pDoc->m_szDocL . " " . $this->m_pDoc->m_szDocR . "\n");
                    
                    $out="Item:" . $szPerfectMatch . " ". $szOverallMatch . " " . $DocL->filename . " " . $DocR->filename . "\n";
                    echo($out. "<br>");
                    fprintf($this->reportGen->m_fLog, $out);

                    // $m_pReport->InsertItem(nItem,szPerfectMatch);
                    // $m_pReport->SetItemText(nItem,1,szOverallMatch);
                    // $m_pReport->SetItemText(nItem,2,m_pDoc->m_szDocL);
                    // $m_pReport->SetItemText(nItem,3,m_pDoc->m_szDocR);
                    // $m_pReport->EnsureVisible(nItem,FALSE);
                    // $m_pReport->Update(nItem);
                }
            }
        }
        fprintf($this->reportGen->m_fLog,"Done Comparing Documents\n\n");
        $this->reportGen->FinishReports($this);

        $szMessage="Done. Total CPU Time:". strval($this->reportGen->m_Time) ." seconds\n<br>";
        echo($szMessage);
    }
}