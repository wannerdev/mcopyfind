<?php
namespace plagiarism_mcopyfind\compare;

include('./document.php');
include('./settings.php');
include('./heapsort.php');
include('./words.php');
include('./generate_report.php');
include('./compare_functions.php');


class load_documents
{

    //set wordNumber to 0
    public $documents = [];
    public $settings;
    public $wordsize=10000;
    public $wordInc=1000;

    function __construct(){
        $this->settings=new settings();
        
    }

    function testMain()
    {
        //loop until EOF
        //count wordAmount  - counting settings
        // echo getcwd(); //working directory -> with namespace workdir changes
        // $file = fopen("t01e.txt", "r");
        // $file2 = fopen("t01.txt", "r");
        // echo $file;
        // echo $file2;
        //$documents.put();
        
        $doc1=new Document();
        // $doc1->definePath("t01.txt");
        $doc1->definePath("text1.pdf");
        //TEST $doc1->definePath("text2.txt");
        $doc2=new Document();
        // $doc2->definePath("t01e.txt");
        $doc2->definePath("text2.pdf");

        $doc1->m_DocumentType=DOC_TYPE_NEW;
        $doc2->m_DocumentType=DOC_TYPE_NEW;

        array_push($this->documents, $doc2);
        array_push($this->documents, $doc1);
        
        $cmp = new compare_functions($this->settings, $this->documents);
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
            $document->Getword($word,$DelimiterType);
            //  $word .= '0';
            $hashes[$wordNumber] = words::WordHash($word);

            // print_r("Word:".$word . "\n");
            //     print_r("Hashes:".$hashes[$wordNumber] . "\n");
            if ($hashes[$wordNumber] != 1) {
                $realwords++;
            }
            $wordNumber++;
            if($wordNumber == $this->wordsize) {
                $this->wordsize += $this->wordInc;
            }
            $word='';
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
        //  var_dump($document->pSortedWordHash);
        $sorted = heapsort::HeapSorting($document->pSortedWordHash,				// sort hash-coded wordAmount (and word numbers)
                              $document->pSortedWordNumber, $wordAmount-1);
        
        $document->pSortedWordHash = $sorted[0];
        $document->pSortedWordNumber = $sorted[1];

        // Test output
        //  print_r("<h1>" . $document->m_WordsTotal . "  Real " . $document->realwords . "</h1>");
        // var_dump($document->pSortedWordNumber);
        //  echo("END: \n\n\n\n");
        //    var_dump($document->pSortedWordHash);
        //foreach ($document->pSortedWordNumber as $element) {
        //      echo ($element . "<br>");
        //  }
        //   echo ("<br><br>");
        // foreach ($document->pSortedWordHash as $element) {
        //        echo ("sorted Hash".$element . "\n");
        // }

        if ($this->settings->m_PhraseLength == 1) $document->firstHash = 0;        // if phraselength is 1 word, compare even the shortest words
        else                                                        // if phrase length is > 1 word, start at first word with more than 3 chars
        {
            $firstLong = 0;
            for ($i = 1; $i < $wordAmount; $i++)                                    // loop for all the words in the document
            {
                if (($document->pSortedWordHash[$i] & 0xFFC00000) != 0)    // if the word is longer than 3 letters, break
                {
                    $firstLong = $i;
                    break;
                }
            }
            $document->firstHash = $firstLong;                    // save the number of the first >3 letter word, or the first word            
            // echo ("setting firstHash: ".$document->firstHash . "\n");
        }
        $document->CloseDocument();
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

// echo file_get_contents("C:\\reports\\matches.html");