<?php
namespace plagiarism_mcopyfind\compare;

use IntlChar;
use Phpml\Math\Set;

class VocabEntry
		{
			public $word="";
			public $usecnt;
			public $hash;
			public $nextEntry;
			public $lastEntry;
		};

class words {

    public $vocab=null;

    /**
     * For now probably broken Repair later if needed
     * 
     * @param type $word
     * */
    /*Maybe try to use a map later
        public $vocab[]=null;
        $vobobj=['id' => words::WordHash($word), 'value' =>1];
        $this->vocab->push($vobobj);
    */
    public function addword($word){
        if($this->vocab ==null){
            $this->vocab= new Set();
            $vobobj=[words::WordHash($word),1];
            $this->vocab->add($vobobj);
        }
        else{
            // $vobobj=[load_documents::WordHash($word),1];//,null,null];
            $vobobj=[words::WordHash($word),1];
            $cache=$this->vocab->count;
            $this->vocab->add($vobobj);
            //obj already in set, increase count
            if($cache==$this->vocab->count){
                $vobobj=$this->vocab->get($vobobj);                
                $this->vocab->remove($vobobj);
                $vobobj[1]++;
                $this->vocab->add($vobobj);
            }
        }

    }   

    public static function WordRemovePunctuation($word) //TODO return word
    {        
        $wordlen=strlen($word);
        for($ccnt=0;$ccnt<$wordlen;$ccnt++)
        {
            if(IntlChar::ispunct($word[$ccnt]))
            {
                for($icnt=$ccnt;$icnt<$wordlen;$icnt++) $word[$icnt]=$word[$icnt+1]; // move the null, too.
                $wordlen--;
                $ccnt--;
            }
        }
        return $word;
    }

    public static function wordxouterpunct($word) //TODO return word
    {
        
        $wordlen=strlen($word);
        for($ccnt=0;$ccnt<$wordlen;$ccnt++)
        {
            if(IntlChar::ispunct($word[$ccnt]))
            {
                for($icnt=$ccnt;$icnt<$wordlen;$icnt++)	$word[$icnt]=$word[$icnt+1]; // move the null, too.
                $wordlen--;
                $ccnt--;
            }
            else break;
        }
        for($ccnt=$wordlen-1;$ccnt>=0;$ccnt--)
        {
            if(IntlChar::ispunct($word[$ccnt]))
            {
                for($icnt=$ccnt;$icnt<$wordlen;$icnt++) $word[$icnt]=$word[$icnt+1];	// move the null, too.
                $wordlen--;
            }
            else break;
        }
        return $word;

    }

    public static function WordRemoveNumbers($word)//TODO return word
    {        
        $wordlen=strlen($word);
        for($ccnt=0;$ccnt<$wordlen;$ccnt++)
        {
            if(IntlChar::isdigit($word[$ccnt]))
            {
                for($icnt=$ccnt;$icnt<$wordlen;$icnt++) $word[$icnt]=$word[$icnt+1];	// move the null, too.
                $wordlen--;
                $ccnt--;
            }
        }
        return $word;
    }

    public static function WordToLowerCase($word) //TODO return word
    {
        $wordlen=strlen($word);
        for($ccnt=0;$ccnt<$wordlen;$ccnt++)
        {
            if(IntlChar::isupper($word[$ccnt])) $word[$ccnt]=IntlChar::tolower($word[$ccnt]);
        }
        return $word;
    }

    public static function WordCheck($word)
    {
        $wordlen=strlen($word);

        if($wordlen < 1) return false;
        if( !IntlChar::isalpha($word[0]) ) return false;
        if( !IntlChar::isalpha($word[$wordlen-1]) ) return false;

        for($ccnt=1;$ccnt<$wordlen-1;$ccnt++)
        {
            if(IntlChar::isalpha(($word[$ccnt])) )continue;
            if( $word[$ccnt] == '-' ) continue;
            if( $word[$ccnt] == '\'' ) continue;
            return false;
        }
        return true;
    }

    
    public static function diff_WordHash($word)
    {
        $length = strlen($word);
        if ($length == null || $length == 0) return 1;    // if word is null, return 1 as hash value
        
        // echo(" Word:" .$word."\n");
        $hash = intval((hexdec(md5($word)))/(pow(2,120-($length))));
        return $hash;
    }

    /** My description of Hashing process
     * inhash    zzzzzzzxxxxxxxxxxxxxxxxxxxxxxxxxxxx
     * ----------------------------------------------
     * 
     * inhash <7 xxxxxxxxxxxxxxxxxxxxxxxxxxx-0000000
     * Logical or                                       =         xxxxxxxxxxxxxxxxxxxxxxxxxxxxzzzzzzz
     * inhash >25                           -zzzzzzz
     * 
     * Logical xor
     *           xxxxxxxxxxxxxxxxxxxxxxxxxxxxzzzzzzz
     * char word                            -yyyyyyy
     * =         xxxxxxxxxxxxxxxxxxxxxxxxxxxxaaaaaaa
     */
    public static function WordHash($word)
    {
        $inhash = 0;
        $charcount = 0;
        $length = strlen($word);
        if ($length == null || $length == 0) return 1;    // if word is null, return 1 as hash value
        else while ($charcount != $length) {
            $inhash = ((($inhash << 7) | ($inhash >> 25)) ^ mb_ord($word[$charcount]));    // xor into the rotateleft(7) of inhash
            $charcount++;                            // and increment the count of characters in the word
        }
    return abs($inhash);#
    }
}