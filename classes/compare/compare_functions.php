<?php

namespace plagiarism_mcopyfind\compare;

const WORD_UNMATCHED=-1;
const WORD_PERFECT=0;
const WORD_FLAW=1;
const WORD_FILTERED=1;
function PercentMatching($firstL,$firstR,$lastL,$lastR,$MatchingWordsPerfect){
    return (200*$MatchingWordsPerfect)/($lastL-$firstL+$lastR-$firstR+2);
}

class compare_functions{

    public $settings;
    public $m_Documents=0; //number of documents
    public $m_pDocs; //array of documents
    
	private $m_MatchMarkL=0;
    private $m_MatchMarkR=0;		// left and right matched word markup list pointers
	private $m_MatchAnchorL=0;
    private $m_MatchAnchorR=0;	// left and right matched string anchors for html files
	private $m_MatchMarkTempL=0;
    private $m_MatchMarkTempR=0;// left and right matched word  markup list pointers - temporary

    function __construct($_settings, $docs){

        $this->m_pDocs = $docs;
        $this->wordHash = array();
        $this->wordNumber = 0;
        $this->realwords = 0;
        $this->m_CompareStep=1000;


        $this->settings= $_settings;
    }

    function ComparePair(Document $docL,Document $docR)
    {
        

        // $wordNumberL=0;
        // $wordNumberR=0;						// word number for left document and right document
        // $wordNumberRedundantL=0;
        // $wordNumberRedundantR=0;		// word number of end of redundant words
        // $counterL=0;
        // $counterR=0;						// word number counter, for loops
        // $firstL=0;$firstR=0;									// first matching word in left document and right document
        // $lastL="";$lastR="";									// last matching word in left document and right document
        // $firstLp="";$firstRp="";								// first perfectly matching word in left document and right document
        // $lastLp="";$lastRp="";									// last perfectlymatching word in left document and right document
        // $firstLx="";$firstRx="";								// first original perfectly matching word in left document and right document
        // $lastLx="";$lastRx="";									// last original perfectlymatching word in left document and right document
        // $flaw=0;											// flaw count
        // $hash=0;                                            // hash value for word							
        // $anchor=0;											// number of current match anchor
        // $i=0;

        $m_MatchingWordsPerfect=0;// count of perfect matches within a single phrase
        $m_MatchingWordsTotalL=0;
        $m_MatchingWordsTotalR=0;

        for($wordNumberL=0;$wordNumberL<$docL->m_WordsTotal;$wordNumberL++)	// loop for all left words
        {
            $this->m_MatchMarkL[$wordNumberL]=WORD_UNMATCHED;		// set the left match markers to "WORD_UNMATCHED"
            $this->m_MatchMarkL[$wordNumberL]=0;					// zero the left match anchors
        }
        for($wordNumberR=0;$wordNumberR<$docR->m_WordsTotal;$wordNumberR++)	// loop for all right words
        {
            $this->m_MatchMarkR[$wordNumberR]=WORD_UNMATCHED;		// set the right match markers to "WORD_UNMATCHED"
            $this->m_MatchAnchorR[$wordNumberR]=0;					// zero the right match anchors
        }

        $wordNumberL=$docL->firstHash;						// start left at first >3 letter word
        $wordNumberR=$docR->firstHash;						// start right at first >3 letter word$m
        $anchor=0;											// start with no html anchors assigned
        
        while ( ($wordNumberL < $docL->m_WordsTotal)			// loop while there are still words to check
                && ($wordNumberR < $docR->m_WordsTotal) )
        {
            // if the next word in the left sorted hash-coded list has been matched
            if( $this->m_MatchMarkL[$docL->m_pSortedWordNumber[$wordNumberL]] != WORD_UNMATCHED )
            {
                $wordNumberL++;								// advance to next left sorted hash-coded word
                continue;
            }

            // if the next word in the right sorted hash-coded list has been matched
            if( $this->m_MatchMarkR[$docR->m_pSortedWordNumber[$wordNumberR]] != WORD_UNMATCHED )
            {
                $wordNumberR++;								// skip to next right sorted hash-coded word
                continue;
            }

            // check for left word less than right word
            if( $docL->m_pSortedWordHash[$wordNumberL] < $docR->m_pSortedWordHash[$wordNumberR] )
            {
                $wordNumberL++;								// advance to next left word
                if ( $wordNumberL >= $docL->m_WordsTotal) break;
                continue;									// and resume looping
            }

            // check for right word less than left word
            if( $docL->m_pSortedWordHash[$wordNumberL] > $docR->m_pSortedWordHash[$wordNumberR] )
            {
                $wordNumberR++;								// advance to next right word
                if ( $wordNumberR >= $docR->m_WordsTotal) break;
                continue;									// and resume looping
            }

            // we have a match, so check redundancy of this words and compare all copies of this word
            $hash=$docL->m_pSortedWordHash[$wordNumberL];
            $WordNumberRedundantL=$wordNumberL;
            $WordNumberRedundantR=$wordNumberR;
            while($WordNumberRedundantL < ($docL->m_WordsTotal - 1))
            {
                if( $docL->m_pSortedWordHash[$WordNumberRedundantL + 1] == $hash ) $WordNumberRedundantL++;
                else break;
            }

            while($WordNumberRedundantR < ($docR->m_WordsTotal - 1))
            {
                if( $docR->m_pSortedWordHash[$WordNumberRedundantR + 1] == $hash ) $WordNumberRedundantR++;
                else break;
            }

            for($counterL=$wordNumberL;$counterL<=$WordNumberRedundantL;$counterL++)	// loop for each copy of this word on the left
            {
                if( $this->m_MatchMarkL[$docL->m_pSortedWordNumber[$counterL]] != WORD_UNMATCHED ) continue;	// skip words that have been matched already
                for($counterR=$wordNumberR;$counterR<=$WordNumberRedundantR;$counterR++)	// loop for each copy of this word on the right
                {
                    if($this->m_MatchMarkR=[$docR->m_pSortedWordNumber[$counterR]] != WORD_UNMATCHED ) continue;	//   skip=0 words that have been matched already

                    // look up and down the hash-coded (not sorted) lists for matches
                    $m_MatchMarkTempL[$docL->m_pSortedWordNumber[$counterL]]=WORD_PERFECT;	// markup word in temporary list at perfection quality
                    $m_MatchMarkTempR[$docR->m_pSortedWordNumber[$counterR]]=WORD_PERFECT;	// marku;
                    //  word=0 in temporary list at perfection quality

                    $firstL = $docL->m_pSortedWordNumber[$counterL]-1;	// start left just before current word
                    $lastL = $docL->m_pSortedWordNumber[$counterL]+1;	// end left just after current word
                    $firstR = $docR->m_pSortedWordNumber[$counterR]-1;  // start right just before current word
                    $lastR=$docR->m_pSortedWordNumber[$counterR]+1;   	// end right just after current word

                    while( ($firstL >= 0) && ($firstR >= 0) )		// if we aren't at the start of either document,
                    {

                        // Note: when we leave this loop, $firstL and $firstR will always point one word before the first match
                        
                        // make sure that left and right words haven't been used in a match before and
                        // that the two words actually match. If so, move up another word and repeat the test.

                        if( $this->m_MatchMarkL[$firstL] != WORD_UNMATCHED ) break;
                        if( $this->m_MatchMarkR[$firstR] != WORD_UNMATCHED ) break;

                        if( $docL->m_pWordHash[$firstL] == $docR->m_pWordHash[$firstR] )
                        {
                            $m_MatchMarkTempL[$firstL]=WORD_PERFECT;		// markup word in temporary list
                            $m_MatchMarkTempR[$firstR]=WORD_PERFECT;		// markup word in temporary list
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
                            $m_MatchMarkTempL[$lastL]=WORD_PERFECT;	// markup word in temporary list
                            $m_MatchMarkTempR[$lastR]=WORD_PERFECT;	// markup word in temporary list
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
                    if($this->m_MismatchTolerance > 0)				// are we accepting imperfect matches?
                    {

                        $firstLx=$firstLp;					// save pointer to word before first perfect match left
                        $firstRx=$firstRp;					// save pointer to word before first perfect match right
                        $lastLx=$lastLp;						// save pointer to word after last perfect match left
                        $lastRx=$lastRp;						// save pointer to word after last perfect match right

                        $flaw=0;							// start with zero $flaw
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
                                $flaw=0;							// having just found a perfect match, we're back to perfect matching
                                $m_MatchMarkTempL[$firstL]=WORD_PERFECT;			// markup word in temporary list
                                $m_MatchMarkTempR[$firstR]=WORD_PERFECT;			// markup word in temporary list
                                $firstLp=$firstL;						// save pointer to first left perfect match
                                $firstRp=$firstR;						// save pointer to first right perfect match
                                $firstL--;							// move up on left
                                $firstR--;							// move up on right
                                continue;
                            }

                            // we're at a flaw, so increase the flaw count
                            $flaw++;
                            if( $flaw > $this->settings->m_MismatchTolerance ) break;	// check for maximum $flaw reached

                            if( ($firstL-1) >= 0 )					// check one word earlier on left (if it exists)
                            {
                                if( $this->m_MatchMarkL[$firstL-1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
                                
                                if( $docL->m_pWordHash[$firstL-1] == $docR->m_pWordHash[$firstR] )
                                {
                                    if( PercentMatching($firstL-1,$firstR,$lastLx,$lastRx,$MatchingWordsPerfect+1) < $this->settings->m_MismatchPercentage ) break;	// are we getting too imperfect?
                                    $m_MatchMarkTempL[$firstL]=WORD_FLAW;	// markup non-matching word in left temporary list
                                    $firstL--;						// move up on left to skip over the flaw
                                    $MatchingWordsPerfect++;			// increment perfect match count;
                                    $flaw=0;						// having just found a perfect match, we're back to perfect matching
                                    $m_MatchMarkTempL[$firstL]=WORD_PERFECT;		// markup word in left temporary list
                                    $m_MatchMarkTempR[$firstR]=WORD_PERFECT;		// markup word in right temporary list
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
                                    if( PercentMatching($firstL,$firstR-1,$lastLx,$lastRx,$MatchingWordsPerfect+1) < $this->settings->m_MismatchPercentage ) break;	// are we getting too imperfect?
                                    $m_MatchMarkTempR[$firstR]=WORD_FLAW;	// markup non-matching word in right temporary list
                                    $firstR--;						// move up on right to skip over the flaw
                                    $MatchingWordsPerfect++;			// increment perfect match count;
                                    $flaw=0;						// having just found a perfect match, we're back to perfect matching
                                    $m_MatchMarkTempL[$firstL]=WORD_PERFECT;		// markup word in left temporary list
                                    $m_MatchMarkTempR[$firstR]=WORD_PERFECT;		// markup word in right temporary list
                                    $firstLp=$firstL;					// save pointer to first left perfect match
                                    $firstRp=$firstR;					// save pointer to first right perfect match
                                    $firstL--;						// move up on left
                                    $firstR--;						// move up on right
                                    continue;
                                }
                            }

                            if( PercentMatching($firstL-1,$firstR-1,$lastLx,$lastRx,$MatchingWordsPerfect) < $this->settings->m_MismatchPercentage ) break;	// are we getting too imperfect?
                            $m_MatchMarkTempL[$firstL]=WORD_FLAW;		// markup word in left temporary list
                            $m_MatchMarkTempR[$firstR]=WORD_FLAW;		// markup word in right temporary list
                            $firstL--;								// move up on left
                            $firstR--;								// move up on right
                        }
            
                        $flaw=0;							// start with zero $flaw
                        while( ($lastL < $docL->m_WordsTotal) && ($lastR < $docR->WordsTotal) )
                        { // if we aren't at the end of either document
                    
                            // Note: when we leave this loop, $lastL and $lastR will always point one word after last match
                            // make sure that left and right words haven't been used in a match before and
                            // that the two words actually match. If so, move up another word and repeat the test.
                            if( $this->m_MatchMarkL[$lastL] != WORD_UNMATCHED ) break;
                            if( $this->m_MatchMarkR[$lastR] != WORD_UNMATCHED ) break;
                            if( $docL->m_pWordHash[$lastL] == $docR->m_pWordHash[$lastR] )
                            {
                                $MatchingWordsPerfect++;				// increment perfect match count;
                                $flaw=0;							// having just found a perfect match, we're back to perfect matching;							m_MatchMarkTempL[$lastL]=WORD_PERFECT;	// markup word in temporary list
                                $m_MatchMarkTempR[$lastR]=WORD_PERFECT;	// markup word in temporary list
                                $lastLp=$lastL;						// save pointer to last left perfect match
                                $lastRp=$lastR;						// save pointer to last right perfect match
                                $lastL++;							// move down on left
                                $lastR++;;						// move down on right
                                continue;
                            }
                            $flaw++;
                            if( $flaw == $this->settings->m_MismatchTolerance ) break;	// check for maximum $flaw reached

                            if( ($lastL+1) < $docL->m_WordsTotal )		// check one word later on left (if it exists)
                            {
                                if( $this->m_MatchMarkL[$lastL+1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
                                
                                if( $docL->m_pWordHash[$lastL+1] == $docR->m_pWordHash[$lastR] )
                                {
                                    if( PercentMatching($firstLx,$firstRx,$lastL+1,$lastR,$MatchingWordsPerfect+1) < $this->settings->m_MismatchPercentage ) break;	// are we getting too imperfect?
                                        $m_MatchMarkTempL[$lastL]=WORD_FLAW;		// marku; non-matching word in left temporary list
                                        $lastL++;						// move down on;left to skip over the flaw
                                        $MatchingWordsPerfect++;			// increment perfect match count;
                                        $flaw=0;						// having just ;ound a perfect match, we're back to perfect matching
                                        $m_MatchMarkTempL[$lastL]=WORD_PERFECT;	// markup word in lefttemporary list
                                        $m_MatchMarkTempR[$lastR]=WORD_PERFECT;	// markup word in right temporary list
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
                                    if( PercentMatching($firstLx,$firstRx,$lastL,$lastR+1,$MatchingWordsPerfect+1) < $this->settings->m_MismatchPercentage ) break;	// are we getting too imperfect?
                                        $m_MatchMarkTempR[$lastR]=WORD_FLAW;		// mar;up non-matching word in right temporary list
                                        $lastR++;						// move down ;n right to skip over the flaw
                                        $MatchingWordsPerfect++;			// increment perfect match count;
                                        $laws=0;						// having jus; found a perfect match, we're back to perfect matching
                                        $m_MatchMarkTempL[$lastL]=WORD_PERFECT;	// markup word in left temporary list
                                        $m_MatchMarkTempR[$lastR]=WORD_PERFECT;	// markup word in right temporary list
                                        $lastLp=$lastL;					// save pointer to last left perfect match
                                        $lastRp=$lastR;					// save pointer to last right perfect match
                                        $lastL++;						// move down on left
                                        $lastR++;;					// move down on right
                                        continue;
                            
                                }
                            }
                            if( PercentMatching($firstLx,$firstRx,$lastL+1,$lastR+1,$MatchingWordsPerfect) < $this->settings->m_MismatchPercentage ) break;	// are we getting too imperfect?
                            $m_MatchMarkTempL[$lastL]=WORD_FLAW;		// marku; word in left temporary list
                            $m_MatchMarkTempR[$lastR]=WORD_FLAW;		// mark;p word in right temporary list
                            $lastL++;								// move down on left
                            $lastR++;								// move;down on right
                        }				
                    }
                    if( $MatchingWordsPerfect >= $this->settings->m_PhraseLength )	// check that phrase has enough perfect matches in it to mark
                    {
                        $anchor++;									// increment anchor count
                        for($i=$firstLp;$i<=$lastLp;$i++)				// loop for all left matched words
                        {
                            $this->m_MatchMarkL[$i]=$m_MatchMarkTempL[$i];	// copy over left matching markup
                            if($m_MatchMarkTempL[$i]==WORD_PERFECT) $m_MatchingWordsPerfect++;	// count the number of perfect matching words (same as for right document)
                            $this->m_MatchMarkL[$i]=$anchor;				// identify the anchor for this phrase
                        }
                        $m_MatchingWordsTotalL += $lastLp-$firstLp+1;	// add the number of words in the matching phrase, whether perfect or flawed matches
                        for($i=$firstRp;$i<=$lastRp;$i++)				// loop for all right matched words
                        {
                            $this->m_MatchMarkR[$i]=$m_MatchMarkTempR[$i];	// copy over right matching markup
                            $this->m_MatchAnchorR[$i]=$anchor;				// identify the anchor for this phrase
                        }
                        $m_MatchingWordsTotalR += $lastRp-$firstRp+1;	// add the number of words in the matching phrase, whether perfect or flawed matches
                    }
                }
            }
            $wordNumberL=$WordNumberRedundantL + 1;			// continue searching after the last redundant word on left
            $wordNumberR=$WordNumberRedundantR + 1;			// continue searching after the last redundant word on right
        }

        $this->settings->m_Compares++;										// increment count of comparisons
        if( ($this->settings->m_Compares % $this->m_CompareStep)	== 0 )				// if count is divisible by 1000,
        {
            syslog(LOG_INFO, "Comparing: ".$this->settings->m_Compares." of ");//.$this->m_TotalCompares);
            // fwprintf(m_fLog,L"Comparing Documents, %d Completed\n",m_Compares);
            // fflush(m_fLog);
        }
        return -1;
    }

    function ComparePairFiltered( document $DocL, document $DocR, document $DocF)
    {
        // $WordNumberL,$WordNumberR,$WordNumberF;			// word number for left document and right document
        // $WordNumberRedundantL,$WordNumberRedundantR;		// word number of end of redundant words
        // $WordNumberRedundantF;
        // $iWordNumberL,$iWordNumberR,$iWordNumberF;			// word number counter, for loops
        // $FirstL,$FirstR,$FirstF;							// first matching word in left document and right document
        // $LastL,$LastR,$LastF;								// last matching word in left document and right document
        // $FirstLp,$FirstRp,$FirstFp;						// first perfectly matching word in left document and right document
        // $LastLp,$LastRp,$LastFp;							// last perfectlymatching word in left document and right document
        // $FirstLx,$FirstRx;								// first original perfectly matching word in left document and right document
        // $LastLx,$LastRx;									// last original perfectlymatching word in left document and right document
        // $Flaws;											// flaw count
        // $$Hash;
        // $MatchingWordsPerfect;							// count of perfect matches within a single phrase
        // $Anchor;											// number of current match $Anchor
        // $i;
    
        $m_MatchingWordsPerfect=0;
        $m_MatchingWordsTotalL=0;
        $m_MatchingWordsTotalR=0;
    
        for($WordNumberL=0;$WordNumberL<$DocL->m_WordsTotal;$WordNumberL++)	// loop for all left words
        {
            $this->m_MatchMarkL[$WordNumberL]=WORD_UNMATCHED;		// set the left match markers to "WORD_UNMATCHED"
            $this->m_MatchMarkL[$WordNumberL]=0;					// zero the left match $Anchors
        }
        for($WordNumberR=0;$WordNumberR<$DocR->m_WordsTotal;$WordNumberR++)	// loop for all right words
        {
            $this->m_MatchMarkR[$WordNumberR]=WORD_UNMATCHED;		// set the right match markers to "WORD_UNMATCHED"
            $this->m_MatchAnchorR[$WordNumberR]=0;					// zero the right match $Anchors
        }
        //
        // filter left document
        //
        $WordNumberL=$DocL->m_FirstHash;						// start left at first >3 letter word
        $WordNumberF=$DocF->m_FirstHash;						// start filter at first >3 letter word
                            
        while ( ($WordNumberL < $DocL->m_WordsTotal)			// loop while there are still words to check
                && ($WordNumberF < $DocF->m_WordsTotal) )
        {
            // if the next word in the left sorted $Hash-coded list has been matched
            if( $this->m_MatchMarkL[$DocL->m_pSortedWordNumber[$WordNumberL]] != WORD_UNMATCHED )
            {
                $WordNumberL++;								// advance to next left sorted $Hash-coded word
                continue;
            }
    
            // check for left word less than filter word
            if( $DocL->m_pSortedWordHash[$WordNumberL] < $DocF->m_pSortedWordHash[$WordNumberF] )
            {
                $WordNumberL++;								// advance to next left word
                if ( $WordNumberL >= $DocL->m_WordsTotal) break;
                continue;									// and resume looping
            }
    
            // check for filter word less than left word
            if( $DocL->m_pSortedWordHash[$WordNumberL] > $DocF->m_pSortedWordHash[$WordNumberF] )
            {
                $WordNumberF++;								// advance to next filter word
                if ( $WordNumberF >= $DocF->m_WordsTotal) break;
                continue;									// and resume looping
            }
    
            // we have a match, so check redundancy of this words and compare all copies of this word
            $$Hash=$DocL->m_pSortedWordHash[$WordNumberL];
            $WordNumberRedundantL=$WordNumberL;
            $WordNumberRedundantF=$WordNumberF;
            while($WordNumberRedundantL < ($DocL->m_WordsTotal - 1))
            {
                if( $DocL->m_pSortedWordHash[$WordNumberRedundantL + 1] == $Hash ) $WordNumberRedundantL++;
                else break;
            }
            while($WordNumberRedundantF < ($DocF->m_WordsTotal - 1))
            {
                if( $DocF->m_pSortedWordHash[$WordNumberRedundantF + 1] == $Hash ) $WordNumberRedundantF++;
                else break;
            }
            for($iWordNumberL=$WordNumberL;$iWordNumberL<=$WordNumberRedundantL;$iWordNumberL++)	// loop for each copy of this word on the left
            {
                if( $this->m_MatchMarkL[$DocL->m_pSortedWordNumber[$iWordNumberL]] != WORD_UNMATCHED ) continue;	// skip words that have been matched already
                for($iWordNumberF=$WordNumberF;$iWordNumberF<=$WordNumberRedundantF;$iWordNumberF++)	// loop for each copy of this word on the filter
                {
                    // look up and down the $Hash-coded (not sorted) lists for matches
                    $FirstL=$DocL->m_pSortedWordNumber[$iWordNumberL]-1;	// start left just before current word
                    $LastL=$DocL->m_pSortedWordNumber[$iWordNumberL]+1;	// end left just after current word
                    $FirstF=$DocF->m_pSortedWordNumber[$iWordNumberF]-1;	// start filter just before current word
                    $LastF=$DocF->m_pSortedWordNumber[$iWordNumberF]+1;	// end filter just after current word
    
                    while( ($FirstL >= 0) && ($FirstF >= 0) )		// if we aren't at the start of either document,
                    {
    
                        // Note: when we leave this loop, FirstL and FirstF will always point one word before the first match
                        
                        // make sure that left word hasn't been used in a match before and
                        // that the two words actually match. If so, move up another word and repeat the test.
    
                        if( $this->m_MatchMarkL[$FirstL] != WORD_UNMATCHED ) break;
    
                        if( $DocL->m_pWordHash[$FirstL] == $DocF->m_pWordHash[$FirstF] )
                        {
                            $FirstL--;									// move up on left
                            $FirstF--;									// move up on filter
                            continue;
                        }
                        break;
                    }
    
                    while( ($LastL < $DocL->m_WordsTotal) && ($LastF < $DocF->m_WordsTotal) ) // if we aren't at the end of either document
                    {
    
                        // Note: when we leave this loop, LastL and LastF will always point one word after last match
                        
                        // make sure that left word hasn't been used in a match before and
                        // that the two words actually match. If so, move up another word and repeat the test.
    
                        if( $this->m_MatchMarkL[$LastL] != WORD_UNMATCHED ) break;
                        if( $DocL->m_pWordHash[$LastL] == $DocF->m_pWordHash[$LastF] )
                        {
                            $LastL++;								// move down on left
                            $LastF++;								// move down on filter
                            continue;
                        }
                        break;
                    }
    
                    $FirstLp=$FirstL+1;						// pointer to first perfect match left
                    $FirstFp=$FirstF+1;						// pointer to first perfect match filter
                    $LastLp=$LastL-1;							// pointer to last perfect match left
                    $LastFp=$LastF-1;							// pointer to last perfect match filter
                    $MatchingWordsPerfect=$LastLp-$FirstLp+1;	// save number of perfect matches
    
                    if( $MatchingWordsPerfect >= $m_FilterPhraseLength )	// check that phrase has enough perfect matches in it to mark
                    {
                        for($i=$FirstLp;$i<=$LastLp;$i++)				// loop for all left matched words
                        {
                            $this->m_MatchMarkL[$i]=WORD_FILTERED;	// mark word as filtered
                        }
                    }
                }
            }
            $WordNumberL=$WordNumberRedundantL + 1;			// continue searching after the last redundant word on left
            $WordNumberF=$WordNumberRedundantF + 1;			// continue searching after the last redundant word on filter
        }
        //
        // filter right document
        //
        $WordNumberR=$DocR->m_FirstHash;						// start right at first >3 letter word
        $WordNumberF=$DocF->m_FirstHash;						// start filter at first >3 letter word
                            
        while ( ($WordNumberR< $DocR->m_WordsTotal)			// loop while there are still words to check
                && ($WordNumberF < $DocF->m_WordsTotal) )
        {
            // if the next word in the right sorted $Hash-coded list has been matched
            if( $this->m_MatchMarkR[$DocR->m_pSortedWordNumber[$WordNumberR]] != WORD_UNMATCHED )
            {
                $WordNumberR++;								// advance to next right sorted $Hash-coded word
                continue;
            }
    
            // check for right word less than filter word
            if( $DocR->m_pSortedWordHash[$WordNumberR] < $DocF->m_pSortedWordHash[$WordNumberF] )
            {
                $WordNumberR++;								// advance to next right word
                if ( $WordNumberR>= $DocR->m_WordsTotal) break;
                continue;									// and resume looping
            }
    
            // check for filter word less than right word
            if( $DocR->m_pSortedWordHash[$WordNumberR] > $DocF->m_pSortedWordHash[$WordNumberF] )
            {
                $WordNumberF++;								// advance to next filter word
                if ( $WordNumberF >= $DocF->m_WordsTotal) break;
                continue;									// and resume looping
            }
    
            // we have a match, so check redundancy of this words and compare all copies of this word
            $Hash=$DocR->m_pSortedWordHash[$WordNumberR];
            $WordNumberRedundantR=$WordNumberR;
            $WordNumberRedundantF=$WordNumberF;
            while($WordNumberRedundantR < ($DocR->m_WordsTotal - 1))
            {
                if( $DocR->m_pSortedWordHash[$WordNumberRedundantR + 1] == $Hash ) $WordNumberRedundantR++;
                else break;
            }
            while($WordNumberRedundantF < ($DocF->m_WordsTotal - 1))
            {
                if( $DocF->m_pSortedWordHash[$WordNumberRedundantF + 1] == $Hash ) $WordNumberRedundantF++;
                else break;
            }
            for($iWordNumberR=$WordNumberR;$iWordNumberR<=$WordNumberRedundantR;$iWordNumberR++)	// loop for each copy of this word on the right
            {
                if( $this->m_MatchMarkR[$DocR->m_pSortedWordNumber[$iWordNumberR]] != WORD_UNMATCHED ) continue;	// skip words that have been matched already
                for($iWordNumberF=$WordNumberF;$iWordNumberF<=$WordNumberRedundantF;$iWordNumberF++)	// loop for each copy of this word on the filter
                {
                    // look up and down the $Hash-coded (not sorted) lists for matches
                    $FirstR=$DocR->m_pSortedWordNumber[$iWordNumberR]-1;	// start right just before current word
                    $LastR=$DocR->m_pSortedWordNumber[$iWordNumberR]+1;	// end right just after current word
                    $FirstF=$DocF->m_pSortedWordNumber[$iWordNumberF]-1;	// start filter just before current word
                    $LastF=$DocF->m_pSortedWordNumber[$iWordNumberF]+1;	// end filter just after current word
    
                    while( ($FirstR >= 0) && ($FirstF >= 0) )		// if we aren't at the start of either document,
                    {
    
                        // Note: when we leave this loop, FirstR and FirstF will always point one word before the first match
                        
                        // make sure that right word hasn't been used in a match before and
                        // that the two words actually match. If so, move up another word and repeat the test.
    
                        if( $this->m_MatchMarkR[$FirstR] != WORD_UNMATCHED ) break;
    
                        if( $DocR->m_pWordHash[$FirstR] == $DocF->m_pWordHash[$FirstF] )
                        {
                            $FirstR--;									// move up on right
                            $FirstF--;									// move up on filter
                            continue;
                        }
                        break;
                    }
    
                    while( ($LastR < $DocR->m_WordsTotal) && ($LastF < $DocF->m_WordsTotal) ) // if we aren't at the end of either document
                    {
    
                        // Note: when we leave this loop, LastR and LastF will always point one word after last match
                        
                        // make sure that right word hasn't been used in a match before and
                        // that the two words actually match. If so, move up another word and repeat the test.
    
                        if( $this->m_MatchMarkR[$LastR] != WORD_UNMATCHED ) break;
                        if( $DocR->m_pWordHash[$LastR] == $DocF->m_pWordHash[$LastF] )
                        {
                            $LastR++;								// move down on right
                            $LastF++;								// move down on filter
                            continue;
                        }
                        break;
                    }
    
                    $FirstRp=$FirstR+1;						// pointer to first perfect match right
                    $FirstFp=$FirstF+1;						// pointer to first perfect match filter
                    $LastRp=$LastR-1;							// pointer to last perfect match right
                    $LastFp=$LastF-1;							// pointer to last perfect match filter
                    $MatchingWordsPerfect=$LastRp-$FirstRp+1;	// save number of perfect matches
    
                    if( $MatchingWordsPerfect >= $m_FilterPhraseLength )	// check that phrase has enough perfect matches in it to mark
                    {
                        for($i=$FirstRp;$i<=$LastRp;$i++)				// loop for all right matched words
                        {
                            $this->m_MatchMarkR[$i]=WORD_FILTERED;	// mark word as filtered
                        }
                    }
                }
            }
            $WordNumberR=$WordNumberRedundantR + 1;			// continue searching after the last redundant word on right
            $WordNumberF=$WordNumberRedundantF + 1;			// continue searching after the last redundant word on filter
        }
        //
        // now do the actual comparison between left and right documents
        //
        $WordNumberL=$DocL->m_FirstHash;						// start left at first >3 letter word
        $WordNumberR=$DocR->m_FirstHash;						// start right at first >3 letter word
    
        $Anchor=0;											// start with no html $Anchors assigned
                            
        while ( ($WordNumberL < $DocL->m_WordsTotal)			// loop while there are still words to check
                && ($WordNumberR< $DocR->m_WordsTotal) )
        {
            // if the next word in the left sorted $Hash-coded list has been matched
            if( $this->m_MatchMarkL[$DocL->m_pSortedWordNumber[$WordNumberL]] != WORD_UNMATCHED )
            {
                $WordNumberL++;								// advance to next left sorted $Hash-coded word
                continue;
            }
    
            // if the next word in the right sorted $Hash-coded list has been matched
            if( $this->m_MatchMarkR[$DocR->m_pSortedWordNumber[$WordNumberR]] != WORD_UNMATCHED )
            {
                $WordNumberR++;								// skip to next right sorted $Hash-coded word
                continue;
            }
    
            // check for left word less than right word
            if( $DocL->m_pSortedWordHash[$WordNumberL] < $DocR->m_pSortedWordHash[$WordNumberR] )
            {
                $WordNumberL++;								// advance to next left word
                if ( $WordNumberL >= $DocL->m_WordsTotal) break;
                continue;									// and resume looping
            }
    
            // check for right word less than left word
            if( $DocL->m_pSortedWordHash[$WordNumberL] > $DocR->m_pSortedWordHash[$WordNumberR] )
            {
                $WordNumberR++;								// advance to next right word
                if ( $WordNumberR>= $DocR->m_WordsTotal) break;
                continue;									// and resume looping
            }
    
            // we have a match, so check redundancy of this words and compare all copies of this word
            $Hash=$DocL->m_pSortedWordHash[$WordNumberL];
            $WordNumberRedundantL=$WordNumberL;
            $WordNumberRedundantR=$WordNumberR;
            while($WordNumberRedundantL < ($DocL->m_WordsTotal - 1))
            {
                if( $DocL->m_pSortedWordHash[$WordNumberRedundantL + 1] == $Hash ) $WordNumberRedundantL++;
                else break;
            }
            while($WordNumberRedundantR < ($DocR->m_WordsTotal - 1))
            {
                if( $DocR->m_pSortedWordHash[$WordNumberRedundantR + 1] == $Hash ) $WordNumberRedundantR++;
                else break;
            }
            for($iWordNumberL=$WordNumberL;$iWordNumberL<=$WordNumberRedundantL;$iWordNumberL++)	// loop for each copy of this word on the left
            {
                if( $this->m_MatchMarkL[$DocL->m_pSortedWordNumber[$iWordNumberL]] != WORD_UNMATCHED ) continue;	// skip words that have been matched already
                for($iWordNumberR=$WordNumberR;$iWordNumberR<=$WordNumberRedundantR;$iWordNumberR++)	// loop for each copy of this word on the right
                {
                    if( $this->m_MatchMarkR[$DocR->m_pSortedWordNumber[$iWordNumberR]] != WORD_UNMATCHED ) continue;	// skip words that have been matched already
    
                    // look up and down the $Hash-coded (not sorted) lists for matches
                    $m_MatchMarkTempL[$DocL->m_pSortedWordNumber[$iWordNumberL]]=WORD_PERFECT;	// markup word in temporary list at perfection quality
                    $m_MatchMarkTempR[$DocR->m_pSortedWordNumber[$iWordNumberR]]=WORD_PERFECT;	// markup word in temporary list at perfection quality
                    
                    $FirstL=$DocL->m_pSortedWordNumber[$iWordNumberL]-1;	// start left just before current word
                    $LastL=$DocL->m_pSortedWordNumber[$iWordNumberL]+1;	// end left just after current word
                    $FirstR=$DocR->m_pSortedWordNumber[$iWordNumberR]-1;	// start right just before current word
                    $LastR=$DocR->m_pSortedWordNumber[$iWordNumberR]+1;	// end right just after current word
    
                    while( ($FirstL >= 0) && ($FirstR >= 0) )		// if we aren't at the start of either document,
                    {
    
                        // Note: when we leave this loop, FirstL and FirstR will always point one word before the first match
                        
                        // make sure that left and right words haven't been used in a match before and
                        // that the two words actually match. If so, move up another word and repeat the test.
    
                        if( $this->m_MatchMarkL[$FirstL] != WORD_UNMATCHED ) break;
                        if( $this->m_MatchMarkR[$FirstR] != WORD_UNMATCHED ) break;
    
                        if( $DocL->m_pWordHash[$FirstL] == $DocR->m_pWordHash[$FirstR] )
                        {
                            $m_MatchMarkTempL[$FirstL]=WORD_PERFECT;		// markup word in temporary list
                            $m_MatchMarkTempR[$FirstR]=WORD_PERFECT;		// markup word in temporary list
                            $FirstL--;									// move up on left
                            $FirstR--;									// move up on right
                            continue;
                        }
                        break;
                    }
    
                    while( ($LastL < $DocL->m_WordsTotal) && ($LastR < $DocR->m_WordsTotal) ) // if we aren't at the end of either document
                    {
    
                        // Note: when we leave this loop, LastL and LastR will always point one word after last match
                        
                        // make sure that left and right words haven't been used in a match before and
                        // that the two words actually match. If so, move up another word and repeat the test.
    
                        if( $this->m_MatchMarkL[$LastL] != WORD_UNMATCHED ) break;
                        if( $this->m_MatchMarkR[$LastR] != WORD_UNMATCHED ) break;
                        if( $DocL->m_pWordHash[$LastL] == $DocR->m_pWordHash[$LastR] )
                        {
                            $m_MatchMarkTempL[$LastL]=WORD_PERFECT;	// markup word in temporary list
                            $m_MatchMarkTempR[$LastR]=WORD_PERFECT;	// markup word in temporary list
                            $LastL++;								// move down on left
                            $LastR++;								// move down on right
                            continue;
                        }
                        break;
                    }
    
                    $FirstLp=$FirstL+1;						// pointer to first perfect match left
                    $FirstRp=$FirstR+1;						// pointer to first perfect match right
                    $LastLp =$LastL-1;							// pointer to last perfect match left
                    $LastRp =$LastR-1;							// pointer to last perfect match right
                    $MatchingWordsPerfect=$LastLp-$FirstLp+1;	// save number of perfect matches
    
                    if($m_MismatchTolerance > 0)				// are we accepting imperfect matches?
                    {
    
                        $FirstLx=$FirstLp;					// save pointer to word before first perfect match left
                        $FirstRx=$FirstRp;					// save pointer to word before first perfect match right
                        $LastLx =$LastLp;						// save pointer to word after last perfect match left
                        $LastRx =$LastRp;						// save pointer to word after last perfect match right
    
                        $Flaws=0;							// start with zero flaws
                        while( ($FirstL >= 0) && ($FirstR >= 0) )		// if we aren't at the start of either document,
                        {
    
                            // Note: when we leave this loop, FirstL and FirstR will always point one word before the first reportable match
                            
                            // make sure that left and right words haven't been used in a match before and
                            // that the two words actually match. If so, move up another word and repeat the test.
                            if( $this->m_MatchMarkL[$FirstL] != WORD_UNMATCHED ) break;
                            if( $this->m_MatchMarkR[$FirstR] != WORD_UNMATCHED ) break;
                            if( $DocL->m_pWordHash[$FirstL] == $DocR->m_pWordHash[$FirstR] )
                            {
                                $MatchingWordsPerfect++;				// increment perfect match count;
                                $Flaws=0;							// having just found a perfect match, we're back to perfect matching
                                $m_MatchMarkTempL[$FirstL]=WORD_PERFECT;			// markup word in temporary list
                                $m_MatchMarkTempR[$FirstR]=WORD_PERFECT;			// markup word in temporary list
                                $FirstLp=$FirstL;						// save pointer to first left perfect match
                                $FirstRp=$FirstR;						// save pointer to first right perfect match
                                $FirstL--;							// move up on left
                                $FirstR--;							// move up on right
                                continue;
                            }
    
                            // we're at a flaw, so increase the flaw count
                            $Flaws++;
                            if( $Flaws > $m_MismatchTolerance ) break;	// check for maximum flaws reached
                            
                            if( ($FirstL-1) >= 0 )					// check one word earlier on left (if it exists)
                            {
                                if( $this->m_MatchMarkL[$FirstL-1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
                                
                                if( $DocL->m_pWordHash[$FirstL-1] == $DocR->m_pWordHash[$FirstR] )
                                {
                                    if( $PercentMatching($FirstL-1,$FirstR,$LastLx,$LastRx,$MatchingWordsPerfect+1) < $m_MismatchPercentage ) break;	// are we getting too imperfect?
                                    $m_MatchMarkTempL[$FirstL]=WORD_FLAW;	// markup non-matching word in left temporary list
                                    $FirstL--;						// move up on left to skip over the flaw
                                    $MatchingWordsPerfect++;			// increment perfect match count;
                                    $Flaws=0;						// having just found a perfect match, we're back to perfect matching
                                    $m_MatchMarkTempL[$FirstL]=WORD_PERFECT;		// markup word in left temporary list
                                    $m_MatchMarkTempR[$FirstR]=WORD_PERFECT;		// markup word in right temporary list
                                    $FirstLp=$FirstL;					// save pointer to first left perfect match
                                    $FirstRp=$FirstR;					// save pointer to first right perfect match
                                    $FirstL--;						// move up on left
                                    $FirstR--;						// move up on right
                                    continue;
                                }
                            }
    
                            if( ($FirstR-1) >= 0 )					// check one word earlier on right (if it exists)
                            {
                                if( $this->m_MatchMarkR[$FirstR-1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
    
                                if( $DocL->m_pWordHash[$FirstL] == $DocR->m_pWordHash[$FirstR-1] )
                                {
                                    if( PercentMatching($FirstL,$FirstR-1,$LastLx,$LastRx,$MatchingWordsPerfect+1) < $m_MismatchPercentage ) break;	// are we getting too imperfect?
                                    $m_MatchMarkTempR[$FirstR]=WORD_FLAW;	// markup non-matching word in right temporary list
                                    $FirstR--;						// move up on right to skip over the flaw
                                    $MatchingWordsPerfect++;			// increment perfect match count;
                                    $Flaws=0;						// having just found a perfect match, we're back to perfect matching
                                    $m_MatchMarkTempL[$FirstL]=WORD_PERFECT;		// markup word in left temporary list
                                    $m_MatchMarkTempR[$FirstR]=WORD_PERFECT;		// markup word in right temporary list
                                    $FirstLp=$FirstL;					// save pointer to first left perfect match
                                    $FirstRp=$FirstR;					// save pointer to first right perfect match
                                    $FirstL--;						// move up on left
                                    $FirstR--;						// move up on right
                                    continue;
                                }
                            }
    
                            if( PercentMatching($FirstL-1,$FirstR-1,$LastLx,$LastRx,$MatchingWordsPerfect) < $m_MismatchPercentage ) break;	// are we getting too imperfect?
                            $m_MatchMarkTempL[$FirstL]=WORD_FLAW;		// markup word in left temporary list
                            $m_MatchMarkTempR[$FirstR]=WORD_FLAW;		// markup word in right temporary list
                            $FirstL--;								// move up on left
                            $FirstR--;								// move up on right
                        }
            
                        $Flaws=0;							// start with zero flaws
                        while( ($LastL < $DocL->m_WordsTotal) && ($LastR < $DocR->m_WordsTotal) ) // if we aren't at the end of either document
                        {
    
                            // Note: when we leave this loop, LastL and LastR will always point one word after last match
                            
                            // make sure that left and right words haven't been used in a match before and
                            // that the two words actually match. If so, move up another word and repeat the test.
                            if( $this->m_MatchMarkL[$LastL] != WORD_UNMATCHED ) break;
                            if( $this->m_MatchMarkR[$LastR] != WORD_UNMATCHED ) break;
                            if( $DocL->m_pWordHash[$LastL] == $DocR->m_pWordHash[$LastR] )
                            {
                                $MatchingWordsPerfect++;				// increment perfect match count;
                                $Flaws=0;							// having just found a perfect match, we're back to perfect matching
                                $m_MatchMarkTempL[$LastL]=WORD_PERFECT;	// markup word in temporary list
                                $m_MatchMarkTempR[$LastR]=WORD_PERFECT;	// markup word in temporary list
                                $LastLp=$LastL;						// save pointer to last left perfect match
                                $LastRp=$LastR;						// save pointer to last right perfect match
                                $LastL++;							// move down on left
                                $LastR++;							// move down on right
                                continue;
                            }
    
                            $Flaws++;
                            if( $Flaws == $m_MismatchTolerance ) break;	// check for maximum flaws reached
                                
                            if( ($LastL+1) < $DocL->m_WordsTotal )		// check one word later on left (if it exists)
                            {
                                if( $this->m_MatchMarkL[$LastL+1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
                                
                                if( $DocL->m_pWordHash[$LastL+1] == $DocR->m_pWordHash[$LastR] )
                                {
                                    if( PercentMatching($FirstLx,$FirstRx,$LastL+1,$LastR,$MatchingWordsPerfect+1) < $m_MismatchPercentage ) break;	// are we getting too imperfect?
                                    $m_MatchMarkTempL[$LastL]=WORD_FLAW;		// markup non-matching word in left temporary list
                                    $LastL++;						// move down on left to skip over the flaw
                                    $MatchingWordsPerfect++;			// increment perfect match count;
                                    $Flaws=0;						// having just found a perfect match, we're back to perfect matching
                                    $m_MatchMarkTempL[$LastL]=WORD_PERFECT;	// markup word in lefttemporary list
                                    $m_MatchMarkTempR[$LastR]=WORD_PERFECT;	// markup word in right temporary list
                                    $LastLp=$LastL;					// save pointer to last left perfect match
                                    $LastRp=$LastR;					// save pointer to last right perfect match
                                    $LastL++;						// move down on left
                                    $LastR++;						// move down on right
                                    continue;
                                }
                            }
    
                            if( ($LastR+1) < $DocR->m_WordsTotal )	// check one word later on right (if it exists)
                            {
                                if( $this->m_MatchMarkR[$LastR+1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
    
                                if( $DocL->m_pWordHash[$LastL] == $DocR->m_pWordHash[$LastR+1] )
                                {
                                    if( PercentMatching($FirstLx,$FirstRx,$LastL,$LastR+1,$MatchingWordsPerfect+1) < $m_MismatchPercentage ) break;	// are we getting too imperfect?
                                    $m_MatchMarkTempR[$LastR]=WORD_FLAW;		// markup non-matching word in right temporary list
                                    $LastR++;						// move down on right to skip over the flaw
                                    $MatchingWordsPerfect++;			// increment perfect match count;
                                    $Flaws=0;						// having just found a perfect match, we're back to perfect matching
                                    $m_MatchMarkTempL[$LastL]=WORD_PERFECT;	// markup word in left temporary list
                                    $m_MatchMarkTempR[$LastR]=WORD_PERFECT;	// markup word in right temporary list
                                    $LastLp=$LastL;					// save pointer to last left perfect match
                                    $LastRp=$LastR;					// save pointer to last right perfect match
                                    $LastL++;						// move down on left
                                    $LastR++;						// move down on right
                                    continue;
                                }
                            }
    
                            if( PercentMatching($FirstLx,$FirstRx,$LastL+1,$LastR+1,$MatchingWordsPerfect) < $m_MismatchPercentage ) break;	// are we getting too imperfect?
                            $m_MatchMarkTempL[$LastL]=WORD_FLAW;		// markup word in left temporary list
                            $m_MatchMarkTempR[$LastR]=WORD_FLAW;		// markup word in right temporary list
                            $LastL++;								// move down on left
                            $LastR++;								// move down on right
                        }
                    }
                    if( $MatchingWordsPerfect >= $m_PhraseLength )	// check that phrase has enough perfect matches in it to mark
                    {
                        $Anchor++;									// increment $Anchor count
                        for($i=$FirstLp;$i<=$LastLp;$i++)				// loop for all left matched words
                        {
                            $this->m_MatchMarkL[$i]=$m_MatchMarkTempL[$i];	// copy over left matching markup
                            if($m_MatchMarkTempL[$i]==WORD_PERFECT) $m_MatchingWordsPerfect++;	// count the number of perfect matching words (same as for right document)
                            $this->m_MatchMarkL[$i]=$Anchor;				// identify the $Anchor for this phrase
                        }
                        $m_MatchingWordsTotalL += $LastLp-$FirstLp+1;	// add the number of words in the matching phrase, whether perfect or flawed matches
                        for($i=$FirstRp;$i<=$LastRp;$i++)				// loop for all right matched words
                        {
                            $this->m_MatchMarkR[$i]=$m_MatchMarkTempR[$i];	// copy over right matching markup
                            $this->m_MatchAnchorR[$i]=$Anchor;				// identify the $Anchor for this phrase
                        }
                        $m_MatchingWordsTotalR += $LastRp-$FirstRp+1;	// add the number of words in the matching phrase, whether perfect or flawed matches
                    }
                }
            }
            $WordNumberL=$WordNumberRedundantL + 1;			// continue searching after the last redundant word on left
            $WordNumberR=$WordNumberRedundantR + 1;			// continue searching after the last redundant word on right
        }
    
        $m_Compares++;										// increment count of comparisons
        if( ($m_Compares%$m_CompareStep)	== 0 )				// if count is divisible by 1000,
        {
            fprintf($m_fLog,"Comparing Documents, ". $m_Compares ." Completed\n",);
            fflush($m_fLog);
        }
        return -1;
    }

    function RunComparison() {
        //  $DocL;$DocR;								// document number of left document and right document
        // $szMessage;									// status messages
        // $i;												// local index counter
        // $irvalue;
        $g_abort = false;					    		// abort signal when true
        $settings =new settings();
       // $this-> = new compare_functions($settings);
        $reportGen = new generate_report($settings);
        $loader = new load_documents($settings);
	    $irvalue = $reportGen->SetupReport(); if($irvalue > -1) return $irvalue;		// setup reporting files	
        
        fprintf($reportGen->m_fLog,"Starting to Load and Hash-Code Documents\n");					// log loading step
        // $m_pStatus->SetWindowTextW(L"Loading and Hash-Coding Documents");  todo replace with html output
        
        for($i=0;$i<$this->m_Documents;$i++)			// loop for all document entries
        {
            if($g_abort)
            {
                //m_pStatus->SetWindowTextW(L"Comparison Aborted");
                return "ERR_ABORT";
            }
            // $m_pProgress->SetPos(i*100/m_pDoc->m_Documents);
            // $szMessage= "Loading: ". $doc->m_szDocumentName;
            // $m_pStatus->SetWindowTextW($szMessage);

            $irvalue = $loader->loadDocument(($this->m_pDocs)); if($irvalue > -1) return $irvalue;			// load this document
            
        }

        fprintf($reportGen->m_fLog,"Done Loading Documents\n");		// Finish loading step log
        fprintf($reportGen->m_fLog,"Starting to Compare Documents\n");		// Finish loading step log
        // $m_pStatus->SetWindowTextW(L"Starting to Compare Documents");  todo replace with html output
        
        //think about info box javascript box?
        // $m_pStatus->SetWindowTextW(L"Comparing Documents");

        // progress bar not implemented for now
        // $m_pProgress->SetPos(0);

        // $this->SetupProgressReports(DOC_TYPE_OLD,DOC_TYPE_NEW,DOC_TYPE_NEW);

        for($DocL=0;$DocL<$this->m_Documents;$DocL++)			// for all possible left documents
        {
            $this->m_pDocL = $this->m_pDocs + $DocL;	// obtain a quick pointer to the left document

            for($DocR=0;$DocR<$DocL;$DocR++)					// for all possible right documents
            {
                $this->m_pDocR = $this->m_pDocs + $DocR;	// obtain a quick pointer to the right document

                if($g_abort){
                    // m_pStatus->SetWindowTextW(L"Comparison Aborted");
                    return "ERR_ABORT";
                }

                if( ($this->m_pDocL->m_DocumentType == "DOC_TYPE_OLD") && ($this->_pDoc->m_pDocR->m_DocumentType == "DOC_TYPE_OLD") ) continue;	// don't compare an old document with an old document

                $irvalue = $this->ComparePair($this->m_pDocL,$this->m_pDocR); if($irvalue > -1) return $irvalue;			// compare the two documents
                
                if( ($this->m_Compares%$this->m_CompareStep)	== 0 )				// if count is divisible by 1000,
                {
                    fprintf($reportGen->m_fLog,"Comparing Documents,". $this->m_pDoc->m_Compares . " Completed\n");		// step log
                    // $szMessage.Format(L"Comparing Documents, %d Completed",m_pDoc->m_Compares);
                    // $m_pStatus->SetWindowTextW(szMessage);
                    // $m_pProgress->SetPos(int((100.0*double(m_pDoc->m_Compares))/double(m_pDoc->m_TotalCompares)));
                }
                
                if($this->m_MatchingWordsPerfect>=$this->m_WordThreshold)		// if there are enough matches to report,
                {
                    $this->m_MatchingDocumentPairs++;				// increment count of matched pairs of documents
                    $reportGen->ReportMatchedPair();

                    //m_report is simply a list-window where we can see the matches of the comparison
                    // $nItem=$m_pReport->GetItemCount();

                    $szPerfectMatch= $this->m_MatchingWordsPerfect. "(" . 100*$this->m_MatchingWordsPerfect/$this->m_pDocL->m_WordsTotal . "L," . 100*$this->m_MatchingWordsPerfect/$this->m_pDocR->m_WordsTotal . "R)"; 
                
                    $szOverallMatch= $this->m_MatchingWordsTotalL . "(". 100*$this->m_MatchingWordsTotalL/$this->m_pDocL->m_WordsTotal . "%)L;" . "," . $this->m_MatchingWordsTotalR . "(". 100*$this->m_MatchingWordsTotalR/$this->m_pDocR->m_WordsTotal . "%)R";
                    fprintf($reportGen->m_fLog,"Item:" . $szPerfectMatch . " ". $szOverallMatch . " " . $this->m_pDoc->m_szDocL . " " . $this->m_pDoc->m_szDocR . "\n");
                    // $m_pReport->InsertItem(nItem,szPerfectMatch);
                    // $m_pReport->SetItemText(nItem,1,szOverallMatch);
                    // $m_pReport->SetItemText(nItem,2,m_pDoc->m_szDocL);
                    // $m_pReport->SetItemText(nItem,3,m_pDoc->m_szDocR);
                    // $m_pReport->EnsureVisible(nItem,FALSE);
                    // $m_pReport->Update(nItem);
                }
            }
        }
        fprintf($reportGen->m_fLog,"Done Comparing Documents\n");
        $reportGen->FinishReports();

        $szMessage="Done. Total CPU Time:". $reportGen->m_StartTicks->format($reportGen->m_StartTicks::RSS) ." seconds";
        echo($szMessage);
    }
}