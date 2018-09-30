<?php 
/**
 * Copyright (c) 2012-present, webtek.at
 * All rights reserved.
 * 
 * Author: Claus Schabetsberger
 *
 * This source code is licensed under the GPL-v3-style license found in the
 * LICENSE file in the root directory of this source tree.
 */

    class ICal {
        
        const ICAL_VERSION = "2.0";
        const MAX_LINE_LENGTH = 70;
        const LINE_BREAK = "\r\n";
        const DATE_FORMAT = "Y-m-d H:i:s";
        
        private $config;
        private $header;
        private $footer;
        private $content;
        
        /**
         * Creates an ICal instance
         * @param $config: The ical configuration array. It must define the following settings:
         * <ul>
         * <li>productId: Identifier of the product that creates the ical file.</li>
         * <li>eventIdPrefix: Identifier prefix for an event.</li>
         * <li>eventCreationDate: Date when the ical file is created.</li>
         * <li>organizerName: Name of the event organizer (optional).</li>
         * <li>organizerEmail: E-Mail Address of the event organizer (optional).</li>
         * </ul>
         */
        public function __construct($config) {
            $this->config = $config;
            
            $this->header = "BEGIN:VCALENDAR".ICal::LINE_BREAK;
            $this->header .= "VERSION:".ICal::ICAL_VERSION.ICal::LINE_BREAK;
            $this->header .= "PRODID:".$this->config['productId'].ICal::LINE_BREAK;
            $this->header .= "METHOD:PUBLISH".ICal::LINE_BREAK;
            
            $this->content = "";
            $this->footer = "END:VCALENDAR".ICal::LINE_BREAK;
        }
        
        /**
         * Adds an event to the event list.
         * @param $id: Event-ID
         * @param $summary: Short description of the event
         * @param $description: Long description of the event
         * @param $location: Location of the event
         * @param $beginDate: Start date
         * @param $endDate: End date
         */
        public function appendEvent($id, $summary, $description, $location, $beginDate, $endDate) {
            $this->content .= "BEGIN:VEVENT".ICal::LINE_BREAK;
            $this->content .= $this->limitLineMaxLength("UID:".$this->config['eventIdPrefix'].$id).ICal::LINE_BREAK;
            $this->content .= $this->limitLineMaxLength("DTSTAMP:".$this->getTimestampInUTC($this->config['eventCreationDate'])).ICal::LINE_BREAK;
            $this->content .= $this->limitLineMaxLength("DTSTART:".$this->getTimestampInUTC($beginDate)).ICal::LINE_BREAK;
            $this->content .= $this->limitLineMaxLength("DTEND:".$this->getTimestampInUTC($endDate)).ICal::LINE_BREAK;
            $this->content .= $this->limitLineMaxLength("SUMMARY:".$this->getPrintableText($summary, false)).ICal::LINE_BREAK;
            
            if(isset($description) && !$this->isEmptyValue($description)) {
                $this->content .= $this->limitLineMaxLength("DESCRIPTION:".$this->getPrintableText($description, false)).ICal::LINE_BREAK;
                $this->content .= $this->limitLineMaxLength("X-ALT-DESC;FMTTYPE=text/html:".$this->getPrintableText($description, true)).ICal::LINE_BREAK;
            }
            
            $this->content .= $this->limitLineMaxLength("LOCATION:".$this->getPrintableText($location, false)).ICal::LINE_BREAK;
            $this->content .= $this->limitLineMaxLength("ORGANIZER;CN=".$this->getOrganizerText()).ICal::LINE_BREAK;
            $this->content .= $this->limitLineMaxLength("CLASS:PUBLIC").ICal::LINE_BREAK;
            $this->content .= "END:VEVENT".ICal::LINE_BREAK;
        }
        
        private function getTimestampInUTC($date) {
            $time = strtotime($date);
            $gmtTime = strtotime(gmdate(ICal::DATE_FORMAT, $time));
            return date("Ymd\THis\Z", $gmtTime);
        }
        
        private function getPrintableText($text, $allowHtml) {
            if($allowHtml) {
                $text = $this->replaceInvalidEventData($text);
                $text = str_replace(array("\r", "\n"), array("", "<br />"), $text);
                
                $html = "<!DOCTYPE HTML PUBLIC \"\"-//W3C//DTD HTML 3.2//EN\"\">";
                $html .= "<HTML>";
                $html .= "<BODY>";
                $html .= $text;
                $html .= "</BODY>";
                $html .= "</HTML>";
                
                return $html;
            } else {
                $text = $this->replaceBrWithNewLinesTags($text);
                $text = strip_tags($text);
                
                return $this->replaceInvalidEventData($text);
            }
        }
        
        private function getOrganizerText() {
            $result = "";
            
            if(isset($this->config['organizerName']) && !$this->isEmptyValue($this->config['organizerName'])) {
                $result .= "\"";
                $result .= $this->config['organizerName'];
                $result .= "\"";
            }
            
            if(isset($this->config['organizerEmail']) && !$this->isEmptyValue($this->config['organizerEmail'])) {
                if(!$this->isEmptyValue($result)) {
                    $result .= ":MAILTO:";
                }
                
                $result .= $this->config['organizerEmail'];
            }
            
            return $result;
        }
        
        private function limitLineMaxLength($text) {
            return $this->wordWrapAfterLineLength($text, ICal::MAX_LINE_LENGTH, "\r\n ");
        }
        
        private function wordWrapAfterLineLength($str, $width = 75, $break = "\n") {
            $strLength = mb_strlen($str);
            $wrappedText = "";
            $pos = 0;
            
            while($pos < $strLength) {
                $chunkLength = min($width, $strLength - $pos);
                
                if(!$this->isEmptyValue($wrappedText)) {
                    $wrappedText .= $break;
                }
                
                $wrappedText .= mb_substr($str, $pos, $chunkLength);
                $pos += $chunkLength;
            }
            
            return $wrappedText;
        }
        
        private function replaceInvalidEventData($data) {
            if(!isset($data)) {
                return "";
            }
            
            $tmp = str_replace(array(
                ",",
                ";",
            ), array(
                "\\,",
                "\\;",
            ), $data);
            
            return $this->ensureBrTagFormat($tmp);
        }
        
        private function replaceBrWithNewLinesTags($html) {
            return preg_replace("/<br\W*?\/>/", "\\n", $html);
        }
        
        private function ensureBrTagFormat($html) {
            return preg_replace("/<br\W*?\/>/", "<br />", $html);
        }
        
        private function isEmptyValue($data) {
            return strlen(trim($data)) == 0;
        }
        
        /**
         * Saves all previously appended events to a .ics file
         * @param $filepath: ICS file path
         * @return: true on success, otherwise false
         */
        public function save($filepath) {
            return file_put_contents($filepath, $this->toString()) > 0 && file_exists($filepath);
        }
        
        /**
         * Creates a string representation of the current ical export
         * @return: String of the current ical export
         */
        public function toString() {
            $str = $this->header;
            $str .= $this->content;
            $str .= $this->footer;
            
            return $str;
        }
    }
?>
