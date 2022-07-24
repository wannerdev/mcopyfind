<?php
namespace plagiarism_mcopyfind\compare;

// echo getcwd() . __DIR__; //working directory -> with namespace workdir changes
require(__DIR__.'\document.php');
require(__DIR__.'\settings.php');
require(__DIR__.'\heapsort.php');
require(__DIR__.'\words.php');
require(__DIR__.'\generate_report.php');
require(__DIR__.'\compare_functions.php');



class load_documents
{

    //set wordNumber to 0
    public $documents = [];
    private static $settings;
    public $wordsize=10000;
    public $wordInc=1000;
    public $wordsFunc;

    function __construct(){
        self::$settings=new settings();        
        $this->wordsFunc = new words();
    }

    static function getSettings(){
        if(self::$settings == null){
            self::$settings = new settings();
            echo("Settings not loaded, using defaults");
        }
        return self::$settings; 
    }

    function testMain()
    {
        //count wordAmount  - counting settings
        // echo getcwd(); //working directory -> with namespace workdir changes
        
        $doc1=new Document("fund1.pdf");
        // $doc1=new Document("t01.txt");
        //$doc1->definePath("fund1.pdf");

        $doc2=new Document("fund2.pdf");
        // $doc2=new Document("t01e.txt");

        $doc1->m_DocumentType=DOC_TYPE_NEW;
        $doc2->m_DocumentType=DOC_TYPE_NEW;

        array_push($this->documents, $doc2);
        array_push($this->documents, $doc1);
        
        $cmp = new compare_functions( $this->documents,-1);
        $cmp->RunComparison($this);
    }

    /**
     * Maybe make static to improve performance later
     */
    function loadDocument($document)
    {

        $wordNumber = 0;
        $wordAmount = 0;
        $hashes = [];
        $realwords = 0;
        $word = '';
        $DelimiterType = DEL_TYPE_NONE;
        // echo "\n################################\n";
        // echo "Document:".$document->path . "\n";
        // echo "\n################################\n";
        // var_dump($document->file);
        while ($DelimiterType != DEL_TYPE_EOF) {
            $word='';
            $document->Getword($word,$DelimiterType);
            if(settings::$m_bIgnorePunctuation) $this->wordsFunc->WordRemovePunctuation($word);	// if ignore punctuation is active, remove punctuation
            if(settings::$m_bIgnoreOuterPunctuation) $this->wordsFunc->wordxouterpunct($word);	// if ignore outer punctuation is active, remove outer punctuation
            if(settings::$m_bIgnoreNumbers) $this->wordsFunc->WordRemoveNumbers($word);			// if ignore numbers is active, remove numbers
            if(settings::$m_bIgnoreCase) $this->wordsFunc->WordToLowerCase($word);				// if ignore case is active, remove case
            // echo "WOrd: ".$word."\n";
            // echo "WOrdlength: ".strlen($word);
            if(settings::$m_bSkipLongWords & (strlen($word) > settings::$m_SkipLength) )continue;	// if skip too-long words is active, skip them
            if(settings::$m_bSkipNonwords & (!$this->wordsFunc->WordCheck($word)) )continue;		// if skip nonwords is active, skip them
            
    
            // print_r("$word:".$word . "\n");
            // print_r("Hashes:".$hashes[$wordNumber] . "\n");
            // if ($hashes[$wordNumber] != 1) {
            //     $realwords++;
            // }
            
            if($wordNumber == $this->wordsize) {
                $this->wordsize += $this->wordInc;
            }

            $hashes[$wordNumber] = words::WordHash($word);
            $wordNumber++;
            // echo "WOrd: ".$word."\n";
        }

        $wordAmount = $wordNumber;              // save number of wordAmount
        $document->m_WordsTotal=$wordAmount;	// save number of wordAmount in document entry

        for ($i = 0; $i < $wordAmount; $i++)            // loop for all the wordAmount in the document
        {
            $document->m_pWordHash[$i] = $hashes[$i];                // copy over hash-coded wordAmount
            $document->pSortedWordNumber[$i] = $i;                    // copy over word numbers
            $document->pSortedWordHash[$i] = $hashes[$i];        // copy over hash-coded wordAmount
        }
        // echo("START:");
        // var_dump($document->pSortedWordHash);
        $sorted = heapsort::HeapSorting($document->pSortedWordHash,				// sort hash-coded wordAmount (and word numbers)
                              $document->pSortedWordNumber, $wordAmount-1);
        
        $document->pSortedWordHash = $sorted[0];
        $document->pSortedWordNumber = $sorted[1];

        // Test output
        // print_r("<h1>" . $document->m_WordsTotal . "  Real " . $document->realwords . "</h1>");
        // var_dump($document->pSortedWordNumber);
        // echo("END: \n\n\n\n");
        // var_dump($document->pSortedWordHash);
        // foreach ($document->pSortedWordNumber as $element) {
        //   echo ($element . "<br>");
        // }

        // echo ("<br><br>");
        // foreach ($document->pSortedWordHash as $element) {
        //        echo ("sorted Hash".$element . "\n");
        // }

        if (settings::$m_PhraseLength == 1) $document->firstHash = 0;        // if phraselength is 1 word, compare even the shortest words
        else                                                        // if phrase length is > 1 word, start at first word with more than 3 chars
        {
            $firstLong = 0;
            for ($i = 1; $i < $wordAmount; $i++)                                    // loop for all the words in the document
            {
                if ((ord($document->pSortedWordHash[$i]) & 0xFFC00000) != 0)    // if the word is longer than 3 letters, break
                {
                    $firstLong = $i;
                    break;
                }
            }
            $document->firstHash = $firstLong;                    // save the number of the first >3 letter word, or the first word            
            // echo ("setting firstHash: ".$document->firstHash . "\n");
        }
    }

    //Guess how this works Probably
    //what it does is it takes 10000 Words of one document - wordAmount or characters?
    // generates hashes for each word in every document (sorted by occurance basically)
    // then creates a sorted copy of the the hashes (sorted by hash value)
    // and then compares the heaps?


    //then generate html by exporting the document and marking the saved findings
    //Excluding vocab functionality for now,
    //specific word hash function

    //Improvment idea define header and footer to ignore
    //needs page number with range of words
    //List of pages with start number of a page,
    // Comparing starts always at page+ header and ends at page-footer //before comparing you ask if the word is inside the page range
}



//Testcase 1
     $test = new load_documents();
     $test->testMain();
      file_get_contents("C:\\moodle\\server\\moodle\\plagiarism\\mcopyfind\\reports\\-1matches.html");