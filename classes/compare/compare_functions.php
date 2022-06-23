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

    
    public $m_MismatchTolerance=2;
    public $m_Compares=0;
    public $m_PhraseLength = 6;
    public $m_FilterPhraseLength = 6;
    public $m_WordThreshold = 100;
    public $m_SkipLength = 20;
    public $m_MismatchPercentage = 80;
    public $m_bBriefReport = false;
    public $m_bIgnoreCase = false;
    public $m_bIgnoreNumbers = false;
    public $m_bIgnoreOuterPunctuation = false;
    public $m_bIgnorePunctuation = false;
    public $m_bSkipLongWords = false;
    public $m_bSkipNonwords = false;
    public $m_bBasic_Characters = false;

    function __construct($settings){
        $this->wordHash = array();
        $this->wordNumber = 0;
        $this->realwords = 0;
        $this->m_CompareStep=1000;

        $this->m_MismatchTolerance=$settings->m_MismatchTolerance;
        $this->m_Compares=$settings->m_Compares;
        $this->m_PhraseLength =$settings->m_PhraseLength;
        $this->m_FilterPhraseLength = $settings->m_FilterPhraseLength ;
        $this->m_WordThreshold = $settings->m_WordThreshold;
        $this->m_SkipLength = $settings->m_SkipLength;
        $this->m_MismatchPercentage = $settings->m_MismatchPercentage;
        $this->m_bBriefReport = $settings->m_bBriefReport;
        $this->m_bIgnoreCase = $settings->m_bIgnoreCase;
        $this->m_bIgnoreNumbers = $settings->m_bIgnoreNumbers;
        $this->m_bIgnoreOuterPunctuation = $settings->m_bIgnoreOuterPunctuation;
        $this->m_bIgnorePunctuation = $settings->m_bIgnorePunctuation;
        $this->m_bSkipLongWords = $settings->m_bSkipLongWords;
        $this->m_bSkipNonwords = $settings->m_bSkipNonwords;
        $this->m_bBasic_Characters = $settings->m_bBasic_Characters;
    }

    function ComparePair(Document $docL,Document $docR)
    {
        

        $wordNumberL=0;
        $wordNumberR=0;						// word number for left document and right document
        $wordNumberRedundantL=0;
        $wordNumberRedundantR=0;		// word number of end of redundant words
        $counterL=0;
        $counterR=0;						// word number counter, for loops
        $firstL=0;$firstR=0;									// first matching word in left document and right document
        $lastL="";$lastR="";									// last matching word in left document and right document
        $firstLp="";$firstRp="";								// first perfectly matching word in left document and right document
        $lastLp="";$lastRp="";									// last perfectlymatching word in left document and right document
        $firstLx="";$firstRx="";								// first original perfectly matching word in left document and right document
        $lastLx="";$lastRx="";									// last original perfectlymatching word in left document and right document
        $flaw=0;											// flaw count
        $hash=0;                                            // hash value for word							
        $anchor=0;											// number of current match anchor
        $i=0;

        $m_MatchingWordsPerfect=0;// count of perfect matches within a single phrase
        $m_MatchingWordsTotalL=0;
        $m_MatchingWordsTotalR=0;

        for($wordNumberL=0;$wordNumberL<$docL->m_WordsTotal;$wordNumberL++)	// loop for all left words
        {
            $m_MatchMarkL[$wordNumberL]=WORD_UNMATCHED;		// set the left match markers to "WORD_UNMATCHED"
            $m_MatchAnchorL[$wordNumberL]=0;					// zero the left match anchors
        }
        for($wordNumberR=0;$wordNumberR<$docR->m_WordsTotal;$wordNumberR++)	// loop for all right words
        {
            $m_MatchMarkR[$wordNumberR]=WORD_UNMATCHED;		// set the right match markers to "WORD_UNMATCHED"
            $m_MatchAnchorR[$wordNumberR]=0;					// zero the right match anchors
        }

        $wordNumberL=$docL->firstHash;						// start left at first >3 letter word
        $wordNumberR=$docR->firstHash;						// start right at first >3 letter word$m
        $anchor=0;											// start with no html anchors assigned
        
        while ( ($wordNumberL < $docL->m_WordsTotal)			// loop while there are still words to check
                && ($wordNumberR < $docR->m_WordsTotal) )
        {
            // if the next word in the left sorted hash-coded list has been matched
            if( $m_MatchMarkL[$docL->m_pSortedWordNumber[$wordNumberL]] != WORD_UNMATCHED )
            {
                $wordNumberL++;								// advance to next left sorted hash-coded word
                continue;
            }

            // if the next word in the right sorted hash-coded list has been matched
            if( $m_MatchMarkR[$docR->m_pSortedWordNumber[$wordNumberR]] != WORD_UNMATCHED )
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
                if( $m_MatchMarkL[$docL->m_pSortedWordNumber[$counterL]] != WORD_UNMATCHED ) continue;	// skip words that have been matched already
                for($counterR=$wordNumberR;$counterR<=$WordNumberRedundantR;$counterR++)	// loop for each copy of this word on the right
                {
                    if($m_MatchMarkR=[$docR->m_pSortedWordNumber[$counterR]] != WORD_UNMATCHED ) continue;	//   skip=0 words that have been matched already

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

                        if( $m_MatchMarkL[$firstL] != WORD_UNMATCHED ) break;
                        if( $m_MatchMarkR[$firstR] != WORD_UNMATCHED ) break;

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
                        if( $m_MatchMarkL[$lastL] != WORD_UNMATCHED ) break;
                        if( $m_MatchMarkR[$lastR] != WORD_UNMATCHED ) break;
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
                            if( $m_MatchMarkL[$firstL] != WORD_UNMATCHED ) break;
                            if( $m_MatchMarkR[$firstR] != WORD_UNMATCHED ) break;
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
                            if( $flaw > $this->m_MismatchTolerance ) break;	// check for maximum $flaw reached

                            if( ($firstL-1) >= 0 )					// check one word earlier on left (if it exists)
                            {
                                if( $m_MatchMarkL[$firstL-1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
                                
                                if( $docL->m_pWordHash[$firstL-1] == $docR->m_pWordHash[$firstR] )
                                {
                                    if( PercentMatching($firstL-1,$firstR,$lastLx,$lastRx,$MatchingWordsPerfect+1) < $this->m_MismatchPercentage ) break;	// are we getting too imperfect?
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
                                if( $m_MatchMarkR[$firstR-1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word

                                if( $docL->m_pWordHash[$firstL] == $docR->m_pWordHash[$firstR-1] )
                                {
                                    if( PercentMatching($firstL,$firstR-1,$lastLx,$lastRx,$MatchingWordsPerfect+1) < $this->m_MismatchPercentage ) break;	// are we getting too imperfect?
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

                            if( PercentMatching($firstL-1,$firstR-1,$lastLx,$lastRx,$MatchingWordsPerfect) < $this->m_MismatchPercentage ) break;	// are we getting too imperfect?
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
                            if( $m_MatchMarkL[$lastL] != WORD_UNMATCHED ) break;
                            if( $m_MatchMarkR[$lastR] != WORD_UNMATCHED ) break;
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
                            if( $flaw == $this->m_MismatchTolerance ) break;	// check for maximum $flaw reached

                            if( ($lastL+1) < $docL->m_WordsTotal )		// check one word later on left (if it exists)
                            {
                                if( $m_MatchMarkL[$lastL+1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
                                
                                if( $docL->m_pWordHash[$lastL+1] == $docR->m_pWordHash[$lastR] )
                                {
                                    if( PercentMatching($firstLx,$firstRx,$lastL+1,$lastR,$MatchingWordsPerfect+1) < $this->m_MismatchPercentage ) break;	// are we getting too imperfect?
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
                                if( $m_MatchMarkR[$lastR+1] != WORD_UNMATCHED ) break;	// make sure we haven't already matched this word
                                if( $docL->m_pWordHash[$lastL] == $docR->m_pWordHash[$lastR+1] )
                                {
                                    if( PercentMatching($firstLx,$firstRx,$lastL,$lastR+1,$MatchingWordsPerfect+1) < $this->m_MismatchPercentage ) break;	// are we getting too imperfect?
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
                            if( PercentMatching($firstLx,$firstRx,$lastL+1,$lastR+1,$MatchingWordsPerfect) < $this->m_MismatchPercentage ) break;	// are we getting too imperfect?
                            $m_MatchMarkTempL[$lastL]=WORD_FLAW;		// marku; word in left temporary list
                            $m_MatchMarkTempR[$lastR]=WORD_FLAW;		// mark;p word in right temporary list
                            $lastL++;								// move down on left
                            $lastR++;								// move;down on right
                        }				
                    }
                    if( $MatchingWordsPerfect >= $this->m_PhraseLength )	// check that phrase has enough perfect matches in it to mark
                    {
                        $anchor++;									// increment anchor count
                        for($i=$firstLp;$i<=$lastLp;$i++)				// loop for all left matched words
                        {
                            $m_MatchMarkL[$i]=$m_MatchMarkTempL[$i];	// copy over left matching markup
                            if($m_MatchMarkTempL[$i]==WORD_PERFECT) $m_MatchingWordsPerfect++;	// count the number of perfect matching words (same as for right document)
                            $m_MatchAnchorL[$i]=$anchor;				// identify the anchor for this phrase
                        }
                        $m_MatchingWordsTotalL += $lastLp-$firstLp+1;	// add the number of words in the matching phrase, whether perfect or flawed matches
                        for($i=$firstRp;$i<=$lastRp;$i++)				// loop for all right matched words
                        {
                            $m_MatchMarkR[$i]=$m_MatchMarkTempR[$i];	// copy over right matching markup
                            $m_MatchAnchorR[$i]=$anchor;				// identify the anchor for this phrase
                        }
                        $m_MatchingWordsTotalR += $lastRp-$firstRp+1;	// add the number of words in the matching phrase, whether perfect or flawed matches
                    }
                }
            }
            $wordNumberL=$WordNumberRedundantL + 1;			// continue searching after the last redundant word on left
            $wordNumberR=$WordNumberRedundantR + 1;			// continue searching after the last redundant word on right
        }

        $this->m_Compares++;										// increment count of comparisons
        if( ($this->m_Compares % $this->m_CompareStep)	== 0 )				// if count is divisible by 1000,
        {
            syslog(LOG_INFO, "Comparing: ".$this->m_Compares." of ".$this->m_TotalCompares);
            // fwprintf(m_fLog,L"Comparing Documents, %d Completed\n",m_Compares);
            // fflush(m_fLog);
        }
        return -1;
    }
}