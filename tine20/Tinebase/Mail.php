<?php
/**
 * Tine 2.0
 *
 * @package     Tinebase
 * @subpackage  Mail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * This class extends the Zend_Mail class 
 *
 * @package     Tinebase
 * @subpackage  Mail
 */
class Tinebase_Mail extends Zend_Mail
{
    /**
    * email address regexp
    */
    const EMAIL_ADDRESS_REGEXP = '/([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,5})/i';
    
    /**
     * Sender: address
     * @var string
     */
    protected $_sender = null;
    
    /**
     * fallback charset constant
     * 
     * @var string
     */
    const DEFAULT_FALLBACK_CHARSET = 'iso-8859-15';
    
    /**
     * create Tinebase_Mail from Zend_Mail_Message
     * 
     * @param  Zend_Mail_Message  $_zmm
     * @param  string             $_replyBody
     * @return Tinebase_Mail
     */
    public static function createFromZMM(Zend_Mail_Message $_zmm, $_replyBody = null)
    {
        $contentStream = fopen("php://temp", 'r+');
        fputs($contentStream, $_zmm->getContent());
        rewind($contentStream);
        
        $mp = new Zend_Mime_Part($contentStream);
        self::_getMetaDataFromZMM($_zmm, $mp);
        
        // append old body when no multipart/mixed
        if ($_replyBody !== null && $_zmm->headerExists('content-transfer-encoding')) {
            $mp = self::_appendReplyBody($mp, $_replyBody);
        } else {
            $mp->decodeContent();
            $mp->encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE;
        }
        
        $result = new Tinebase_Mail('utf-8');
        $result->setBodyText($mp);
        $result->setHeadersFromZMM($_zmm);
        
        return $result;
    }
    
    /**
     * get meta data (like contentype, charset, ...) from zmm and set it in zmp
     * 
     * @param Zend_Mail_Message $zmm
     * @param Zend_Mime_Part $zmp
     */
    protected static function _getMetaDataFromZMM(Zend_Mail_Message $zmm, Zend_Mime_Part $zmp)
    {
        if ($zmm->headerExists('content-transfer-encoding')) {
            $zmp->encoding = $zmm->getHeader('content-transfer-encoding');
        } else {
            $zmp->encoding = Zend_Mime::ENCODING_7BIT;
        }
        
        if ($zmm->headerExists('content-type')) {
            $contentTypeHeader = Zend_Mime_Decode::splitHeaderField($zmm->getHeader('content-type'));
            
            $zmp->type = $contentTypeHeader[0];
            
            if (isset($contentTypeHeader['boundary'])) {
                $zmp->boundary = $contentTypeHeader['boundary'];
            }
            
            if (isset($contentTypeHeader['charset'])) {
                $zmp->charset = $contentTypeHeader['charset'];
            }
        } else {
            $zmp->type = Zend_Mime::TYPE_TEXT;
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . ' Encoding: ' . $zmp->encoding . ' / type: ' . $zmp->type . ' / charset: ' . $zmp->charset);
    }
    
    /**
     * appends old body to mime part
     * 
     * @param Zend_Mime_Part $mp
     * @param string $replyBody
     * @return Zend_Mime_Part
     */
    protected static function _appendReplyBody(Zend_Mime_Part $mp, $replyBody)
    {
        $decodedContent = Tinebase_Mail::getDecodedContent($mp, NULL, FALSE);
        $type = $mp->type;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::TRACE)) {
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " mp content: " . $decodedContent);
            Tinebase_Core::getLogger()->trace(__METHOD__ . '::' . __LINE__ . " reply body: " . $replyBody);
        }
        
        if ($type === Zend_Mime::TYPE_HTML && preg_match('/(<\/body>[\s\r\n]*<\/html>)/i', $decodedContent, $matches)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . ' Appending reply body to html body.');
            
            $decodedContent = str_replace($matches[1], $replyBody . $matches[1], $decodedContent);
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
                . " Appending reply body to mime text part.");
            
            $decodedContent .= $replyBody;
        }
        
        $mp = new Zend_Mime_Part($decodedContent);
        $mp->charset = 'utf-8';
        $mp->type = $type;
        
        return $mp;
    }
    
    /**
     * Sets the HTML body for the message
     *
     * @param  string|Zend_Mime_Part    $html
     * @param  string    $charset
     *  @param  string    $encoding
     * @return Zend_Mail Provides fluent interface
     */
    public function setBodyHtml($html, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        if ($html instanceof Zend_Mime_Part) {
            $mp = $html;
        } else {
            if ($charset === null) {
                $charset = $this->_charset;
            }
        
            $mp = new Zend_Mime_Part($html);
            $mp->encoding = $encoding;
            $mp->type = Zend_Mime::TYPE_HTML;
            $mp->disposition = Zend_Mime::DISPOSITION_INLINE;
            $mp->charset = $charset;
        }
        
        $this->_bodyHtml = $mp;
    
        return $this;
    }
    
    /**
     * Sets the text body for the message.
     *
     * @param  string|Zend_Mime_Part $txt
     * @param  string $charset
     * @param  string $encoding
     * @return Zend_Mail Provides fluent interface
    */
    public function setBodyText($txt, $charset = null, $encoding = Zend_Mime::ENCODING_QUOTEDPRINTABLE)
    {
        if ($txt instanceof Zend_Mime_Part) {
            $mp = $txt;
        } else {
            if ($charset === null) {
                $charset = $this->_charset;
            }
    
            $mp = new Zend_Mime_Part($txt);
            $mp->encoding = $encoding;
            $mp->type = Zend_Mime::TYPE_TEXT;
            $mp->disposition = Zend_Mime::DISPOSITION_INLINE;
            $mp->charset = $charset;
        }
        
        $this->_bodyText = $mp;

        return $this;
    }
    
    /**
     * set headers
     * 
     * @param Zend_Mail_Message $_zmm
     * @return Zend_Mail Provides fluent interface
     */
    public function setHeadersFromZMM(Zend_Mail_Message $_zmm)
    {
        foreach ($_zmm->getHeaders() as $header => $values) {
            foreach ((array)$values as $value) {
                switch ($header) {
                    case 'content-transfer-encoding':
                    // these are implicitly set by Zend_Mail_Transport_Abstract::_getHeaders()
                    case 'content-type':
                    case 'mime-version':
                        // do nothing
                        break;
                        
                    case 'bcc':
                        $addresses = self::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $this->addBcc($address['address'], $address['name']);
                        }
                        break;
                        
                    case 'cc':
                        $addresses = self::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $this->addCc($address['address'], $address['name']);
                        }
                        break;
                        
                    case 'date':
                        try {
                            $this->setDate($value);
                        } catch (Zend_Mail_Exception $zme) {
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " Could not set date: " . $value);
                            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE))
                                Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " " . $zme);
                            $this->setDate();
                        }
                        break;
                        
                    case 'from':
                        $addresses = self::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $this->setFrom($address['address'], $address['name']);
                        }
                        break;
                        
                    case 'message-id':
                        $this->setMessageId($value);
                        break;
                        
                    case 'return-path':
                        $this->setReturnPath($value);
                        break;
                        
                    case 'subject':
                        $this->setSubject($value);
                        break;
                        
                    case 'to':
                        $addresses = self::parseAdresslist($value);
                        foreach ($addresses as $address) {
                            $this->addTo($address['address'], $address['name']);
                        }
                        break;
                        
                    default:
                        $this->addHeader($header, $value);
                        break;
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Sets Sender-header and sender of the message
     *
     * @param  string    $email
     * @param  string    $name
     * @return Zend_Mail Provides fluent interface
     * @throws Zend_Mail_Exception if called subsequent times
     */
    public function setSender($email, $name = '')
    {
        if ($this->_sender === null) {
            $email = strtr($email,"\r\n\t",'???');
            $this->_from = $email;
            $this->_storeHeader('Sender', $this->_encodeHeader('"'.$name.'"').' <'.$email.'>', true);
        } else {
            throw new Zend_Mail_Exception('Sender Header set twice');
        }
        return $this;
    }
    
    /**
     * Formats e-mail address
     * 
     * NOTE: we always add quotes to the name as this caused problems when name is encoded
     * @see Zend_Mail::_formatAddress
     *
     * @param string $email
     * @param string $name
     * @return string
     */
    protected function _formatAddress($email, $name)
    {
        if ($name === '' || $name === null || $name === $email) {
            return $email;
        } else {
            $encodedName = $this->_encodeHeader($name);
            $format = '"%s" <%s>';
            return sprintf($format, $encodedName, $email);
        }
    }

    /**
     * check if Zend_Mail_Message is/contains calendar iMIP message
     * 
     * @param Zend_Mail_Message $zmm
     * @return boolean
     */
    public static function isiMIPMail(Zend_Mail_Message $zmm)
    {
        foreach ($zmm as $part) {
            if (preg_match('/text\/calendar/', $part->contentType)) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    /**
     * get decoded body content
     * 
     * @param Zend_Mime_Part $zmp
     * @param array $partStructure
     * @param boolean $appendCharsetFilter
     * @return string
     */
    public static function getDecodedContent(Zend_Mime_Part $zmp, $_partStructure = NULL, $appendCharsetFilter = TRUE)
    {
        $charset = self::_getCharset($zmp, $_partStructure);
        if ($appendCharsetFilter) {
            $charset = self::_appendCharsetFilter($zmp, $charset);
        }
        $encoding = ($_partStructure && ! empty($_partStructure['encoding'])) ? $_partStructure['encoding'] : $zmp->encoding;
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__
            . " Trying to decode mime part content. Encoding/charset: " . $encoding . ' / ' . $charset);
        
        // need to set error handler because stream_get_contents just throws a E_WARNING
        set_error_handler('Tinebase_Mail::decodingErrorHandler', E_WARNING);
        try {
            $body = $zmp->getDecodedContent();
            restore_error_handler();
            
        } catch (Tinebase_Exception $e) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__
                . " Decoding of " . $zmp->encoding . '/' . $encoding . ' encoded message failed: ' . $e->getMessage());
            
            // trying to fix decoding problems
            restore_error_handler();
            $zmp->resetStream();
            if (preg_match('/convert\.quoted-printable-decode/', $e->getMessage())) {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Trying workaround for http://bugs.php.net/50363.');
                $body = quoted_printable_decode(stream_get_contents($zmp->getRawStream()));
                $body = iconv($charset, 'utf-8', $body);
            } else {
                if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Try again with fallback encoding.');
                $zmp->appendDecodeFilter(self::_getDecodeFilter());
                set_error_handler('Tinebase_Mail::decodingErrorHandler', E_WARNING);
                try {
                    $body = $zmp->getDecodedContent();
                    restore_error_handler();
                } catch (Tinebase_Exception $e) {
                    restore_error_handler();
                    if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . ' Fallback encoding failed. Trying base64_decode().');
                    $zmp->resetStream();
                    $body = base64_decode(stream_get_contents($zmp->getRawStream()));
                    $body = iconv($charset, 'utf-8', $body);
                }
            }
        }
        
        return $body;
    }
    /**
     * convert charset (and return charset)
     *
     * @param  Zend_Mime_Part  $_part
     * @param  array           $_structure
     * @return string   
     */
    protected static function _getCharset(Zend_Mime_Part $_part, $_structure = NULL)
    {
        return ($_structure && isset($_structure['parameters']['charset'])) 
            ? $_structure['parameters']['charset']
            : ($_part->charset ? $_part->charset : self::DEFAULT_FALLBACK_CHARSET);
    }
    
    /**
     * convert charset (and return charset)
     *
     * @param  Zend_Mime_Part  $_part
     * @param  string          $charset
     * @return string   
     */
    protected static function _appendCharsetFilter(Zend_Mime_Part $_part, $charset)
    {
        if ($charset == 'utf8') {
            $charset = 'utf-8';
        } else if ($charset == 'us-ascii') {
            // us-ascii caused problems with iconv encoding to utf-8
            $charset = self::DEFAULT_FALLBACK_CHARSET;
        } else if (strpos($charset, '.') !== false) {
            // the stream filter does not like charsets with a dot in its name
            // stream_filter_append(): unable to create or locate filter "convert.iconv.ansi_x3.4-1968/utf-8//IGNORE"
            $charset = self::DEFAULT_FALLBACK_CHARSET;
        } else if (iconv($charset, 'utf-8', '') === false) {
            // check if charset is supported by iconv
            $charset = self::DEFAULT_FALLBACK_CHARSET;
        }
        
        $_part->appendDecodeFilter(self::_getDecodeFilter($charset));
        
        return $charset;
    }
    
    /**
     * get decode filter for stream_filter_append
     * 
     * @param string $_charset
     * @return string
     */
    protected static function _getDecodeFilter($_charset = self::DEFAULT_FALLBACK_CHARSET)
    {
        if (in_array(strtolower($_charset), array('iso-8859-1', 'windows-1252', 'iso-8859-15')) && extension_loaded('mbstring')) {
            require_once 'StreamFilter/ConvertMbstring.php';
            $filter = 'convert.mbstring';
        } else {
            $filter = "convert.iconv.$_charset/utf-8//IGNORE";
        }
        
        if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' Appending decode filter: ' . $filter);
        
        return $filter;
    }
    
    /**
     * error exception handler for iconv decoding errors / only gets E_WARNINGs
     *
     * NOTE: PHP < 5.3 don't throws exceptions for Catchable fatal errors per default,
     * so we convert them into exceptions manually
     *
     * @param integer $severity
     * @param string $errstr
     * @param string $errfile
     * @param integer $errline
     * @throws Tinebase_Exception
     * 
     * @todo maybe we can remove that because php 5.3+ is required now
     */
    public static function decodingErrorHandler($severity, $errstr, $errfile, $errline)
    {
        Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . " $errstr in {$errfile}::{$errline} ($severity)");
        
        throw new Tinebase_Exception($errstr);
    }
    
    /**
     * parse address list
     *
     * @param string $_adressList
     * @return array
     */
    public static function parseAdresslist($_addressList)
    {
        if (strpos($_addressList, ',') !== FALSE && substr_count($_addressList, '@') == 1) {
            // we have a comma in the name -> do not split string!
            $addresses = array($_addressList);
        } else {
            // create stream to be used with fgetcsv
            $stream = fopen("php://temp", 'r+');
            fputs($stream, $_addressList);
            rewind($stream);
            
            // alternative solution to create stream; yet untested
            #$stream = fopen('data://text/plain;base64,' . base64_encode($_addressList), 'r');
            
            // split addresses
            $addresses = fgetcsv($stream);
        }
        
        if (! is_array($addresses)) {
            if (Tinebase_Core::isLogLevel(Zend_Log::NOTICE)) Tinebase_Core::getLogger()->notice(__METHOD__ . '::' . __LINE__ . 
                ' Could not parse addresses: ' . var_export($addresses, TRUE));
            return array();
        }
        
        foreach ($addresses as $key => $address) {
            if (preg_match('/(.*)<(.+@[^@]+)>/', $address, $matches)) {
                $name = trim(trim($matches[1]), '"');
                $address = trim($matches[2]);
                $addresses[$key] = array('name' => substr($name, 0, 250), 'address' => $address);
            } else {
                $address = preg_replace('/[,;]*/i', '', $address);
                $addresses[$key] = array('name' => null, 'address' => $address);
            }
        }

        return $addresses;
    }
}
