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

    public function addword($word){
        if($this->vocab ==null){
            $vocab= new Set();
            $vobobj=[words::WordHash($word),1];//,null,null];
            $this->vocab->add($vobobj);
        }
        else{
            // $this->vocab->
            // $vobobj=[load_documents::WordHash($word),1];//,null,null];
            $vobobj=[words::WordHash($word),1];
            $cache=$this->vocab->count;
            $this->vocab->add($vobobj);
            if($cache == $this->vocab->count){
                 $this->vocab->last = [words::WordHash($word),2];
            }
        }

    }   

    public function WordRemovePunctuation($word) //TODO return word
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

    public function wordxouterpunct($word) //TODO return word
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

    public function WordRemoveNumbers($word)//TODO return word
    {
        $$wordlen;
        $$ccnt;
        $$icnt;
        
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

    public function WordToLowerCase($word) //TODO return word
    {
        $wordlen=strlen($word);
        for($ccnt=0;$ccnt<$wordlen;$ccnt++)
        {
            if(IntlChar::isupper($word[$ccnt])) $word[$ccnt]=IntlChar::tolower($word[$ccnt]);
        }
        return $word;
    }

    public function WordCheck($word)
    {
        $wordlen=$word->length();

        $wordlen=strlen($word);

        if($wordlen < 1) return false;
        if( !IntlChar::isalpha($word[0]) ) return false;
        if( !IntlChar::isalpha($word[$wordlen-1]) ) return false;

        for($ccnt=1;$ccnt<$wordlen-1;$ccnt++)
        {
            if(IntlChar::isalpha(($word[$$ccnt])) )continue;
            if( $word[$ccnt] == '-' ) continue;
            if( $word[$ccnt] == '\'' ) continue;
            return false;
        }
        return true;
    }

        /** Description of Hashing process
     * inhash    zzzzzzzxxxxxxxxxxxxxxxxxxxxxxxxxxxx
     * ----------------------------------------------
     * 
     * inhash <7 xxxxxxxxxxxxxxxxxxxxxxxxxxx-0000000
     * Logical or
     * inhash >25                           -zzzzzzz
     * =         xxxxxxxxxxxxxxxxxxxxxxxxxxxxzzzzzzz
     * Logical xor
     * char word                            -yyyyyyy
     * =         xxxxxxxxxxxxxxxxxxxxxxxxxxxxaaaaaaa
     */
    public static function WordHash($word)
    {
        //unsigned long inhash;
        //$inhash;
        $inhash = 0;
        //$word .= " "; 
        $charcount = 0;
        $length = strlen($word);
        if ($length == null || $length == 0) return 1;    // if word is null, return 1 as hash value
        else while ($charcount != $length) {
            $inhash = (($inhash << 7) | ($inhash >> 25)) ^ mb_ord($word[$charcount]);    // xor into the rotateleft(7) of inhash
            $charcount++;                            // and increment the count of characters in the word
        }
        return abs($inhash);
    }
}