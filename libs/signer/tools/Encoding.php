<?php
/**
 * A helper class wrapping around iconv + support for PdfDocEncoding 
 */
class SetaPDF_Tools_Encoding {
    
    static public $pdfDocEncodingToUtf16Be = array(
    //  Char   => Unicode      // Note	Dec   Unicode character name / Character
        "\x00" => "\x00\x00",  // U		0     (NULL) 								
		"\x01" => "\x00\x01",  // U		1     (START OF HEADING)					
    	"\x02" => "\x00\x02",  // U		2     (START OF TEXT)						
		"\x03" => "\x00\x03",  // U		3     (END OF TEXT)							
    	"\x04" => "\x00\x04",  // U		4     (END OF TEXT)							
		"\x05" => "\x00\x05",  // U		5     (END OF TRANSMISSION)					
    	"\x06" => "\x00\x06",  // U		6     (ACKNOWLEDGE)							
		"\x07" => "\x00\x07",  // U		7     (BELL)								
    	"\x08" => "\x00\x08",  // U		8     (BACKSPACE)							
		"\x09" => "\x00\x09",  // SR	9     (CHARACTER TABULATION)					
    	"\x0a" => "\x00\x0a",  // SR	10	  (LINE FEED)							
    	"\x0b" => "\x00\x0b",  // U		11    (LINE TABULATION)								
    	"\x0c" => "\x00\x0c",  // U		12    (FORM FEED)									
    	"\x0d" => "\x00\x0d",  // SR	13    (CARRIAGE RETURN)						
    	"\x0e" => "\x00\x0e",  // U		14    (SHIFT OUT)									
    	"\x0f" => "\x00\x0f",  // U		15    (SHIFT IN)									
    	"\x10" => "\x00\x10",  // U		16    (DATA LINK ESCAPE)							
    	"\x11" => "\x00\x11",  // U		17    (DEVICE CONTROL ONE)							
    	"\x12" => "\x00\x12",  // U		18    (DEVICE CONTROL TWO)							
    	"\x13" => "\x00\x13",  // U		19    (DEVICE CONTROL THREE)						
    	"\x14" => "\x00\x14",  // U		20    (DEVICE CONTROL FOUR)							
    	"\x15" => "\x00\x15",  // U		21    (NEGATIVE ACKNOWLEDGE)					
    	"\x16" => "\x00\x16",  // U		22    (SYNCRONOUS IDLE)								
    	"\x17" => "\x00\x17",  // U		23    (END OF TRANSMISSION BLOCK)		 			
    	"\x18" => "\x02\xd8",  // 		24    (BREVE)										
    	"\x19" => "\x02\xc7",  // 		25    (CARON)										
    	"\x1a" => "\x02\xc6",  // 		26    (MODIFIER LETTER CIRCUMFLEX ACCENT)	
    	"\x1b" => "\x02\xd9",  // 		27    (DOT ABOVE)									
    	"\x1c" => "\x02\xdd",  // 		28    (DOUBLE ACUTE ACCENT)							
    	"\x1d" => "\x02\xdb",  // 		29    (OGONEK)								
    	"\x1e" => "\x02\xda",  // 		30    (RING ABOVE)							
    	"\x1f" => "\x02\xdc",  // 		31    (SMALL TILDE)							
    	"\x20" => "\x00\x20",  // 		32    (SPACE &#32;)
    	"\x21" => "\x00\x21",  // SR	33    (EXCLAMATION MARK)					
    	"\x22" => "\x00\x22",  // SR	34    (QUOTATION MARK (&quot;)				
    	"\x23" => "\x00\x23",  // 		35    (NUMBER SIGN)							
    	"\x24" => "\x00\x24",  // 		36    (DOLLAR SIGN)							
    	"\x25" => "\x00\x25",  // 		37    (PERCENT SIGN)						
    	"\x26" => "\x00\x26",  // 		38    (AMPERSAND (&amp;))					
    	"\x27" => "\x00\x27",  // 		39    (APOSTROPHE (&apos;))					
    	"\x28" => "\x00\x28",  // 		40    (LEFT PARENTHESIS)					
    	"\x29" => "\x00\x29",  // 		41    (RIGHT PARANTHESIS)					
    	"\x2a" => "\x00\x2a",  // 		42    (ASTERISK)							
    	"\x2b" => "\x00\x2b",  // 		43    (PLUG SIGN)							
    	"\x2c" => "\x00\x2c",  // 		44    (COMMA)								
    	"\x2d" => "\x00\x2d",  // 		45    (HYPHEN-MINUX)
    	"\x2e" => "\x00\x2e",  // 		46    (FULL STOP (PERIOD))
    	"\x2f" => "\x00\x2f",  // 		47    (SOLIDUS (slash)))
    	"\x30" => "\x00\x30",  // 		48    (DIGIT ZERO)
    	"\x31" => "\x00\x31",  // 		49    (DIGIT ONE)
    	"\x32" => "\x00\x32",  // 		50    (DIGIT TWO)
    	"\x33" => "\x00\x33",  // 		51    (DIGIT THREE)
    	"\x34" => "\x00\x34",  // 		52    (DIGIT FOUR)
    	"\x35" => "\x00\x35",  // 		53    (DIGIT FIVE)
    	"\x36" => "\x00\x36",  // 		54    (DIGIT SIX)
    	"\x37" => "\x00\x37",  // 		55    (DIGIT SEVEN)
    	"\x38" => "\x00\x38",  // 		56    (DIGIT EIGHT)
    	"\x39" => "\x00\x39",  // 		57    (DIGIT NINE)
    	"\x3a" => "\x00\x3a",  // 		58    (COLON)
    	"\x3b" => "\x00\x3b",  // 		59    (SEMICOLON)
    	"\x3c" => "\x00\x3c",  // SR	60    (LESS THAN SIGN (&lt;))	
    	"\x3d" => "\x00\x3d",  // 		61    (EQUALS SIGN)
    	"\x3e" => "\x00\x3e",  // 		62    (GREATER THAN SIGN (&gt;))
    	"\x3f" => "\x00\x3f",  // 		63    (QUESTION MARK)
    	"\x40" => "\x00\x40",  // 		64    (COMMERCIAL AT)
    	"\x41" => "\x00\x41",  // 		65    (A)	
    	"\x42" => "\x00\x42",  // 		66    (B)
    	"\x43" => "\x00\x43",  // 		67    (C)
    	"\x44" => "\x00\x44",  // 		68    (D)
    	"\x45" => "\x00\x45",  // 		69    (E)
    	"\x46" => "\x00\x46",  // 		70    (F)
    	"\x47" => "\x00\x47",  // 		71    (G)
    	"\x48" => "\x00\x48",  // 		72    (H)
    	"\x49" => "\x00\x49",  // 		73    (I)
    	"\x4a" => "\x00\x4a",  // 		74    (J)
    	"\x4b" => "\x00\x4b",  // 		75    (K)
    	"\x4c" => "\x00\x4c",  // 		76    (L)
    	"\x4d" => "\x00\x4d",  // 		77    (M)
    	"\x4e" => "\x00\x4e",  // 		78    (N)
    	"\x4f" => "\x00\x4f",  // 		79    (O)
    	"\x50" => "\x00\x50",  // 		80    (P)
    	"\x51" => "\x00\x51",  // 		81    (Q)
    	"\x52" => "\x00\x52",  // 		82    (R)
    	"\x53" => "\x00\x53",  // 		83    (S)
    	"\x54" => "\x00\x54",  // 		84    (T)
    	"\x55" => "\x00\x55",  // 		85    (U)
    	"\x56" => "\x00\x56",  // 		86    (V)
    	"\x57" => "\x00\x57",  // 		87    (W)
    	"\x58" => "\x00\x58",  // 		88    (X)
    	"\x59" => "\x00\x59",  // 		89    (Y)
    	"\x5a" => "\x00\x5a",  // 		90    (Z)
    	"\x5b" => "\x00\x5b",  // 		91	  (LEFT SQUARE BRACKET)
    	"\x5c" => "\x00\x5c",  // 		92    (REVERSE SOLIDUS (backslash))
    	"\x5d" => "\x00\x5d",  // 		93    (RIGHT SQUARE BRACKET)	
    	"\x5e" => "\x00\x5e",  // 		94    (CURCUMFLEX ACCENT (hat))
    	"\x5f" => "\x00\x5f",  // 		95    (LOW LINE (SPACING UNDERSCORE))
    	"\x60" => "\x00\x60",  // 		96    (GRAVE ACCENT)
    	"\x61" => "\x00\x61",  // 		97    (a)
    	"\x62" => "\x00\x62",  // 		98    (b)
    	"\x63" => "\x00\x63",  // 		99    (c)
    	"\x64" => "\x00\x64",  // 		100   (d)
    	"\x65" => "\x00\x65",  // 		101   (e)
    	"\x66" => "\x00\x66",  // 		102   (f)
    	"\x67" => "\x00\x67",  // 		103   (g)
    	"\x68" => "\x00\x68",  // 		104   (h)
    	"\x69" => "\x00\x69",  // 		105   (i)
    	"\x6a" => "\x00\x6a",  // 		106   (j)
    	"\x6b" => "\x00\x6b",  // 		107   (k)
    	"\x6c" => "\x00\x6c",  // 		108   (l)
    	"\x6d" => "\x00\x6d",  // 		109   (m)
    	"\x6e" => "\x00\x6e",  // 		110   (n)
    	"\x6f" => "\x00\x6f",  // 		111   (o)
    	"\x70" => "\x00\x70",  // 		112   (p)
    	"\x71" => "\x00\x71",  // 		113   (q)
    	"\x72" => "\x00\x72",  // 		114   (r)
    	"\x73" => "\x00\x73",  // 		115   (s)
    	"\x74" => "\x00\x74",  // 		116   (t)
    	"\x75" => "\x00\x75",  // 		117   (u)
    	"\x76" => "\x00\x76",  // 		118   (v)
    	"\x77" => "\x00\x77",  // 		119   (w)
    	"\x78" => "\x00\x78",  // 		120   (x)
    	"\x79" => "\x00\x79",  // 		121   (y)
    	"\x7a" => "\x00\x7a",  // 		122   (z)    	
    	"\x7b" => "\x00\x7b",  // 		123   (LEFT CURLY BRACKET)
    	"\x7c" => "\x00\x7c",  // 		124   (VERTICAL LINE)
    	"\x7d" => "\x00\x7d",  // 		125   (RIGHT CURLY BRACKET)
    	"\x7e" => "\x00\x7e",  // 		126   (TILDE)
    	"\x7f" => null, 	   // 		127   (UNDEFINED)
    	"\x80" => "\x20\x22",  // 		128   (BULLET)	
    	"\x81" => "\x20\x20",  // 		129   (DAGGER)
    	"\x82" => "\x20\x21",  // 		130   (DOUBLE DAGGER)
    	"\x83" => "\x20\x26",  // 		131   (HORIZONTAL ELLIPSIS)
    	"\x84" => "\x20\x14",  // 		132   (EM DASH)	
    	"\x85" => "\x20\x13",  // 		133   (EN DASH)
    	"\x86" => "\x01\x92",  // 		134   (UNDEFINED)
    	"\x87" => "\x20\x44",  // 		135   (FRACTION SLASH (solidus))
    	"\x88" => "\x20\x39",  // 		136   (SINGLE LEFT-POINTING ANGLE QUOTATION MARK)
    	"\x89" => "\x20\x3a",  // 		137   (SINGLE RIGHT-POINTING ANGLE QUOTATION MARK)	
    	"\x8a" => "\x22\x12",  // 		138   (UNDEFINED)
    	"\x8b" => "\x20\x30",  // 		139   (PER MILE SIGN)
    	"\x8c" => "\x20\x1e",  // 		140   (DOUBLE LOW-9 QUOTATION MARK (quotedblbase))
    	"\x8d" => "\x20\x1c",  // 		141   (LEFT DOUBLE QUOTATION MARK (double quote left))
    	"\x8e" => "\x20\x1d",  // 		142   (RIGHT DOUBLE QUOTATION MARK (quotedblright))	
    	"\x8f" => "\x20\x18",  // 		143   (LEFT SINGLE QUOTATION MARK (quoteleft))
    	"\x90" => "\x20\x19",  // 		144   (RIGHT SINGLE QUOTATION MARK (quoteright))
    	"\x91" => "\x20\x1a",  // 		145   (SINGLE LOW-9 QUOTATION MARK (quotesinglbase))
    	"\x92" => "\x21\x22",  // 		146   (TRADE MARK SIGN)
    	"\x93" => "\xfb\x01",  // 		147   (LATIN SMALL LIGATURE FI)	
    	"\x94" => "\xfb\x02",  // 		148   (LATIN SMALL LIGATURE FL)
    	"\x95" => "\x01\x41",  // 		149   (LATIN CAPITAL LETTER L WITH STROKE)	
    	"\x96" => "\x01\x52",  // 		150   (LATIN CAPITAL LIGATURE OE)
    	"\x97" => "\x01\x60",  // 		151   (LATIN CAPITAL LETTER S WITH CARON)
    	"\x98" => "\x01\x78",  // 		152   (LATIN CAPITAL LETTER Y WITH DIAERESIS)
    	"\x99" => "\x01\x7d",  // 		153   (LATIN CAPITAL LETTER Z WITH CARON)
    	"\x9a" => "\x01\x31",  // 		154   (LATIN SMALL LETTER DOTLESS I)	
    	"\x9b" => "\x01\x42",  // 		155   (LATIN SMALL LETTER L WITH STROKE)
    	"\x9c" => "\x01\x53",  // 		156   (LATIN SMALL LIGATURE OE)	
    	"\x9d" => "\x01\x61",  // 		157   (LATIN SMALL LETTER S WITH CARON)
    	"\x9e" => "\x01\x7e",  // 		158   (LATIN SMALL LETTER Z WITH CARON)
    	"\x9f" => null,  	   // 		159    
    	"\xa0" => "\x20\xac",  // 		160   (EURO SIGN)
    	"\xa1" => "\x00\xa1",  // 		161   (INVERTED EXCLAMATION MARK)	
    	"\xa2" => "\x00\xa2",  // 		162   (CENT SIGN)
    	"\xa3" => "\x00\xa3",  // 		163   (POUND SIGN (sterling))	
    	"\xa4" => "\x00\xa4",  // 		164   (CURRENCY SIGN)
    	"\xa5" => "\x00\xa5",  // 		165   (YEN SIGN)
    	"\xa6" => "\x00\xa6",  // 		166   (BROKEN BAR)
    	"\xa7" => "\x00\xa7",  // 		167   (SECTION SIGN)
    	"\xa8" => "\x00\xa8",  // 		168   (DIAERESIS)	
    	"\xa9" => "\x00\xa9",   // 		169   (COPYRIGHT)
    	"\xaa" => "\x00\xaa",  // 		170   (FEMENINE ORDINAL INDICATOR)	
    	"\xab" => "\x00\xab",  // 		171   (LEFT POINTING DOUBLE ANGLE QUOTATION MARK)
    	"\xac" => "\x00\xac",  // 		172   (NOT SIGN)
    	"\xad" => null,		   // 		173   (UNDEFINED)
    	"\xae" => "\x00\xae",  // 		174   (REGISTERED SIGN)
    	"\xaf" => "\x00\xaf",  // 		175   (MACRON)	
    	"\xb0" => "\x00\xb0",  // 		176   (DEGREE SIGN)
    	"\xb1" => "\x00\xb1",  // 		177   (PLUS-MINUS SIGN)	
    	"\xb2" => "\x00\xb2",  // 		178   (SUPERSCRIPT TWO)
    	"\xb3" => "\x00\xb3",  // 		179   (SUPERSCRIPT THREE)
    	"\xb4" => "\x00\xb4",  // 		180   (ACUTE ACCENT)
    	"\xb5" => "\x00\xb5",  // 		181   (MICRO SIGN)
    	"\xb6" => "\x00\xb6",  // 		182   (PILCROW SIGN)	
    	"\xb7" => "\x00\xb7",  // 		183   (MIDDLE DOT)
    	"\xb8" => "\x00\xb8",  // 		184   (CEDILLA)	
    	"\xb9" => "\x00\xb9",  // 		185   (SUPERSCRIPT ONE)
    	"\xba" => "\x00\xba",  // 		186   (MASCULINE ORDINAL INDICATOR)
    	"\xbb" => "\x00\xbb",  // 		187   (RIGHT-POINTING DOUBLE ANGLE QUOTATION MARK)
    	"\xbc" => "\x00\xbc",  // 		188   (VULGAR FRACTION ONE QUARTER)
    	"\xbd" => "\x00\xbd",  // 		189   (VULGAR FRACTION ONE HALF)	
    	"\xbe" => "\x00\xbe",  // 		190   (VULGAR FRACTION THREE QUARTERS)
    	"\xbf" => "\x00\xbf",  // 		191   (INVERTED QUESTION MARK)	
    	"\xc0" => "\x00\xc0",  // 		192   
    	"\xc1" => "\x00\xc1",  // 		193    
    	"\xc2" => "\x00\xc2",  // 		194
    	"\xc3" => "\x00\xc3",  // 		195 	
    	"\xc4" => "\x00\xc4",  // 		196
    	"\xc5" => "\x00\xc5",  // 		197
    	"\xc6" => "\x00\xc6",  // 		198
    	"\xc7" => "\x00\xc7",  // 		199
    	"\xc8" => "\x00\xc8",  // 		200
    	"\xc9" => "\x00\xc9",  // 		201    
    	"\xca" => "\x00\xca",  // 		202
    	"\xcb" => "\x00\xcb",  // 		203
    	"\xcc" => "\x00\xcc",  // 		204
    	"\xcd" => "\x00\xcd",  // 		205
    	"\xce" => "\x00\xce",  // 		206
    	"\xcf" => "\x00\xcf",  // 		207
    	"\xd0" => "\x00\xd0",  // 		208
    	"\xd1" => "\x00\xd1",  // 		209
    	"\xd2" => "\x00\xd2",  // 		210
    	"\xd3" => "\x00\xd3",  // 		211
    	"\xd4" => "\x00\xd4",  // 		212
    	"\xd5" => "\x00\xd5",  // 		213
    	"\xd6" => "\x00\xd6",  // 		214 
    	"\xd7" => "\x00\xd7",  // 		215
    	"\xd8" => "\x00\xd8",  // 		216
    	"\xd9" => "\x00\xd9",  // 		217
    	"\xda" => "\x00\xda",  // 		218
    	"\xdb" => "\x00\xdb",  // 		219
    	"\xdc" => "\x00\xdc",  // 		220 
    	"\xdd" => "\x00\xdd",  // 		221
    	"\xde" => "\x00\xde",  // 		222
    	"\xdf" => "\x00\xdf",  // 		223
    	"\xe0" => "\x00\xe0",  // 		224
    	"\xe1" => "\x00\xe1",  // 		225
    	"\xe2" => "\x00\xe2",  // 		226
    	"\xe3" => "\x00\xe3",  // 		227
    	"\xe4" => "\x00\xe4",  // 		228	
    	"\xe5" => "\x00\xe5",  // 		229
    	"\xe6" => "\x00\xe6",  // 		230
    	"\xe7" => "\x00\xe7",  // 		231
    	"\xe8" => "\x00\xe8",  // 		232
    	"\xe9" => "\x00\xe9",  // 		233
    	"\xea" => "\x00\xea",  // 		234
    	"\xeb" => "\x00\xeb",  // 		235
    	"\xec" => "\x00\xec",  // 		236
    	"\xed" => "\x00\xed",  // 		237
    	"\xee" => "\x00\xee",  // 		238
    	"\xef" => "\x00\xef",  // 		239
    	"\xf0" => "\x00\xf0",  // 		240
    	"\xf1" => "\x00\xf1",  // 		241
    	"\xf2" => "\x00\xf2",  // 		242
    	"\xf3" => "\x00\xf3",  // 		243
    	"\xf4" => "\x00\xf4",  // 		244
    	"\xf5" => "\x00\xf5",  // 		245
    	"\xf6" => "\x00\xf6",  // 		246		
    	"\xf7" => "\x00\xf7",  // 		247
    	"\xf8" => "\x00\xf8",  // 		248
    	"\xf9" => "\x00\xf9",  // 		249
    	"\xfa" => "\x00\xfa",  // 		250
    	"\xfb" => "\x00\xfb",  // 		251
    	"\xfc" => "\x00\xfc",  // 		252 	
    	"\xfd" => "\x00\xfd",  // 		253
    	"\xfe" => "\x00\xfe",  // 		254
    	"\xff" => "\x00\xff",  // 		255
    );

	/**
	 * Converts a string from UTF-16BE to PdfDocEncoding
	 * 
	 * @param string $string 
	 * @return string
	 */
    static public function utf16BeToPdfDocEncoding($string, $ignore = false, $translit = false) 
    {
    	$newString = '';

     	for ($i = 0, $len = strlen($string); $i < $len; $i += 2 )
     	{
     		if (!isset($string[$i+1])) {
     			trigger_error(__METHOD__.'(): Detected an incomplete multibyte character in input string', E_USER_NOTICE);
     			break;
     		}
     		
     		$search = $string[$i].$string[$i+1];
    		$res = array_search($search, self::$pdfDocEncodingToUtf16Be);
     		
     		if ($res !== false) {
     			$newString .= $res;
     		} else if ($ignore == false) {
	     		if ($translit == true) {
	     			$newString .= "\x3f"; // Character not found, replace by "?"
	     		} else {
	     			trigger_error(__METHOD__.'(): Detected an illegal character in input string', E_USER_NOTICE);
     			}
	     	}	    		
    	}
    	
		return $newString;
    }
    
    /**
	 * Converts a string from PdfDocEncoding to UTF-16BE
	 * 
	 * @param string $string 
	 * @return string
	 */
    static public function pdfDocEncodingtoUtf16Be($string) 
    {
    	$newString = '';

	 	for ($i = 0, $len = strlen($string); $i < $len; $i++ )
     	{
     		$search = $string[$i];
     	    $newString .= self::$pdfDocEncodingToUtf16Be[$search];
    	}
    	
    	return $newString;
    }
    
    /**
	 * Converts a string from one to another encoding
	 * 
	 * A kind of wrapper around iconv plus the seperate processing of PdfDocEncoding
	 * 
	 * @param string $string		The string to convert in $inEncoding
	 * @param string $inEncoding	The "in"-encoding
	 * @param string $outEncoding	The "out"-encoding
	 * @return string
	 */
    static public function convert($string, $inEncoding, $outEncoding) {
    	$_outEncoding = explode("//", $outEncoding);
    	 	
    	if ($inEncoding == $_outEncoding[0]) { 
    		return $string;
    	}
    	
    	$ignore = in_array('IGNORE', $_outEncoding);
    	$translit = in_array('TRANSLIT', $_outEncoding);
    
    	// IN
    	if ($inEncoding == 'PdfDocEncoding') {
    		$string = self::PdfDocEncodingtoUtf16Be($string);
    		$inEncoding = "UTF-16BE";
    	}
    	    	
    	// OUT
    	if ($_outEncoding[0] == 'PdfDocEncoding') {
    		if ($inEncoding != 'UTF-16BE') {
    			$string = iconv($inEncoding, 'UTF-16BE', $string);
    			$inEncoding = "UTF-16BE";
    		}
    		$string = self::Utf16BeToPdfDocEncoding($string, $ignore, $translit);
    	} else {
    		if ($inEncoding == $_outEncoding[0]) {
	    		return $string;
    		}
    		$string = iconv($inEncoding, implode('//', $_outEncoding), $string);
    	}
    	
    	return $string;
    }
    
    /**
     * Converts a PDF string (in PdfDocEncoding or UTF-16BE) to another encoding
     * 
     * This mehtod automatically detects UTF-16BE encoding in the input string and
     * removes the BOM.
     *
     * @param string $string The string to convert in PdfDocEncoding or UTF-16BE
     * @param string $outEncoding The "out"-encoding
     * @return string
     */
    static public function convertPdfString($string, $outEncoding) {
    	$inEncoding = 'PdfDocEncoding';
    	if (strpos($string, "\xFE\xFF") === 0) {
    		$string = substr($string, 2);
            $inEncoding = 'UTF-16BE';
        } 
        
        return self::convert($string, $inEncoding, $outEncoding);
    }
}