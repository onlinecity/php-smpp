<?php
namespace Phpsmpp\Protocol;


/**
 * Class capable of encoding GSM 03.38 default alphabet and packing octets into septets as described by GSM 03.38.
 * Based on mapping: http://www.unicode.org/Public/MAPPINGS/ETSI/GSM0338.TXT
 * 
 * Copyright (C) 2011 OnlineCity
 * Licensed under the MIT license, which can be read at: http://www.opensource.org/licenses/mit-license.php
 * @author hd@onlinecity.dk
 */
class GsmEncoder
{
    /**
     * @param $utf8str
     * @return string the encoded string if the given string fit in the GSM 03.38 encoding (7 bit), or false
     */
    public static function utf8_to_gsm_03_38($utf8str) {
        $gsm_03_38 = self::utf8_to_gsm0338($utf8str);

        $originalNbOfQuestionMarks = substr_count($utf8str, '?');
        $finalNbOfQuestionMarks = substr_count($gsm_03_38, '?');

        if($originalNbOfQuestionMarks !== $finalNbOfQuestionMarks) {
            return false;
        }
        return $gsm_03_38;
    }
	
	/**
	 * Encode an UTF-8 string into GSM 03.38
	 * Since UTF-8 is largely ASCII compatible, and GSM 03.38 is somewhat compatible, unnecessary conversions are removed.
	 * Specials chars such as € can be encoded by using an escape char \x1B in front of a backwards compatible (similar) char.
	 * UTF-8 chars which doesn't have a GSM 03.38 equivalent is replaced with a question mark. 
	 * UTF-8 continuation bytes (\x08-\xBF) are replaced when encountered in their valid places, but 
	 * any continuation bytes outside of a valid UTF-8 sequence is not processed.
	 *
	 * @param string $string
	 * @return string
	 */
	protected static function utf8_to_gsm0338($string)
	{
		$dict = array(
			'@' => "\x00", '£' => "\x01", '$' => "\x02", '¥' => "\x03", 'è' => "\x04", 'é' => "\x05", 'ù' => "\x06", 'ì' => "\x07", 'ò' => "\x08", 'Ç' => "\x09", 'Ø' => "\x0B", 'ø' => "\x0C", 'Å' => "\x0E", 'å' => "\x0F",
			'Δ' => "\x10", '_' => "\x11", 'Φ' => "\x12", 'Γ' => "\x13", 'Λ' => "\x14", 'Ω' => "\x15", 'Π' => "\x16", 'Ψ' => "\x17", 'Σ' => "\x18", 'Θ' => "\x19", 'Ξ' => "\x1A", 'Æ' => "\x1C", 'æ' => "\x1D", 'ß' => "\x1E", 'É' => "\x1F",
			// all \x2? removed
			// all \x3? removed
			// all \x4? removed
			'Ä' => "\x5B", 'Ö' => "\x5C", 'Ñ' => "\x5D", 'Ü' => "\x5E", '§' => "\x5F",
			'¿' => "\x60",
			'ä' => "\x7B", 'ö' => "\x7C", 'ñ' => "\x7D", 'ü' => "\x7E", 'à' => "\x7F",
			'^' => "\x1B\x14", '{' => "\x1B\x28", '}' => "\x1B\x29", '\\' => "\x1B\x2F", '[' => "\x1B\x3C", '~' => "\x1B\x3D", ']' => "\x1B\x3E", '|' => "\x1B\x40", '€' => "\x1B\x65"
		);
		$converted = strtr($string, $dict);
		
		// Replace unconverted UTF-8 chars from codepages U+0080-U+07FF, U+0080-U+FFFF and U+010000-U+10FFFF with a single ?
		return preg_replace('/([\\xC0-\\xDF].)|([\\xE0-\\xEF]..)|([\\xF0-\\xFF]...)/m','?',$converted);
	}

    public static function gsm0338_to_utf8($string)
    {
        $dict = array(
            "\x00" => '@', "\x01" => '£', "\x02" => '$', "\x03" => '¥', "\x04" => 'è',
            "\x05" => 'é', "\x06" => 'ù', "\x07" => 'ì', "\x08" => 'ò', "\x09" => 'Ç',
            "\x0B" => 'Ø', "\x0C" => 'ø', "\x0E" => 'Å', "\x0F" => 'å', "\x10" => 'Δ',
            "\x11" => '_', "\x12" => 'Φ', "\x13" => 'Γ', "\x14" => 'Λ', "\x15" => 'Ω',
            "\x16" => 'Π', "\x17" => 'Ψ', "\x18" => 'Σ', "\x19" => 'Θ', "\x1A" => 'Ξ',
            "\x1C" => 'Æ', "\x1D" => 'æ', "\x1E" => 'ß', "\x1F" => 'É', "\x5B" => 'Ä',
            "\x5C" => 'Ö', "\x5D" => 'Ñ', "\x5E" => 'Ü', "\x5F" => '§', "\x60" => '¿',
            "\x7B" => 'ä', "\x7C" => 'ö', "\x7D" => 'ñ', "\x7E" => 'ü', "\x7F" => 'à',
            "\x1B\x14" => '^', "\x1B\x28" => '{', "\x1B\x29" => '}', "\x1B\x2F" => '\\',
            "\x1B\x3C" => '[', "\x1B\x3D" => '~', "\x1B\x3E" => ']', "\x1B\x40" => '|',
            "\x1B\x65" => '€'
        );
        $converted = strtr($string, $dict);

        return $converted;
    }

    public static function other_to_utf8($encoding, $string) {
        if($encoding == SMPP::ENCODING_GSM_03_38_NAME) {
            return self::gsm0338_to_utf8($string);
        }

        return mb_convert_encoding($string, SMPP::ENCODING_UTF8_NAME, $encoding);
    }

    public static function utf8_to_other($encoding, $string) {
        if($encoding == SMPP::ENCODING_GSM_03_38_NAME) {
            return self::utf8_to_gsm_03_38($string);
        }

        return mb_convert_encoding($string, $encoding, SMPP::ENCODING_UTF8_NAME);
    }

    public static function byte_unicode_to_utf8($byteChar) {
        return self::hexa_unicode_to_utf8(strtoupper(dechex($byteChar)));
    }

    public static function hexa_unicode_to_utf8($hexaChar) {
        return html_entity_decode("&#x".$hexaChar.";", ENT_QUOTES, 'UTF-8');
    }

    public static function utf8_to_usc2($utf8str) {
        return mb_convert_encoding($utf8str, "UCS-2", "UTF-8");
    }

    /**
     * Returns the most appropriated encoding for the given string
     * @param $utf8str
     */
    public static function getMostFittingEncoding($utf8str) {
        $encoding_name = SMPP::ENCODING_GSM_03_38_NAME;
        $i = 0;

        $sevenBitsInt = hexdec("7F"); //UTF-8 hexa.
        $heightBitsInt = hexdec("C3BF"); //UTF-8 hexa. Corresponds to "FF"
        $length = mb_strlen($utf8str, SMPP::ENCODING_UTF8_NAME);

        while($encoding_name != SMPP::ENCODING_UCS2_NAME && $i < $length) {
            $char = mb_substr($utf8str, $i, 1, SMPP::ENCODING_UTF8_NAME);

            if($encoding_name == SMPP::ENCODING_GSM_03_38_NAME) {
                if(self::utf8_to_gsm_03_38($char)) {
                    $i++;
                    continue;
                }
            }

            $charHex = unpack('H*', $char);
            $charInt = hexdec($charHex[1]);

            /*if($charInt > $heightBitsInt) {
                $encoding_name = SMPP::ENCODING_UCS2_NAME;
            }
            else if($charInt > $sevenBitsInt) {
                $encoding_name = SMPP::ENCODING_ISO8859_1_NAME;
            }*/

            //issues with ISO8859_1 --> if the character needs more than 7 bits --> UCS2
            if($charInt > $sevenBitsInt) {
                $encoding_name = SMPP::ENCODING_UCS2_NAME;
            }

            $i++;
        }

        return $encoding_name;
    }
	
	/**
	 * Count the number of GSM 03.38 chars a conversion would contain.
	 * It's about 3 times faster to count than convert and do strlen() if conversion is not required.
	 * 
	 * @param string $utf8String
	 * @return integer
	 */
	public static function countGsm0338Length($utf8String)
	{
		$len = mb_strlen($utf8String,'utf-8');
		$len += preg_match_all('/[\\^{}\\\~€|\\[\\]]/mu',$utf8String,$m);
		return $len;
	}

	/**
	 * Pack an 8-bit string into 7-bit GSM format
	 * Returns the packed string in binary format
	 *
	 * @param string $data
	 * @return string
	 */
	public static function pack7bit($data)
	{
		$l = strlen($data);
		$currentByte = 0;
		$offset = 0;
		$packed = '';
		for ($i = 0; $i < $l; $i++) {
			// cap off any excess bytes
			$septet = ord($data[$i]) & 0x7f;
			// append the septet and then cap off excess bytes
			$currentByte |= ($septet << $offset) & 0xff;
			// update offset
			$offset += 7;

			if ($offset > 7) {
				// the current byte is full, add it to the encoded data.
				$packed .= chr($currentByte);
				// shift left and append the left shifted septet to the current byte
				$currentByte = $septet = $septet >> (7 - ($offset - 8 ));
				// update offset
				$offset -= 8; // 7 - (7 - ($offset - 8))
			}
		}
		if ($currentByte > 0) $packed .= chr($currentByte); // append the last byte

		return $packed;
	}
}