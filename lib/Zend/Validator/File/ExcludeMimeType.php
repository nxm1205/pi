<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Validator\File;

use finfo;
use Zend\Validator\Exception;

/**
 * Validator for the mime type of a file
 */
class ExcludeMimeType extends MimeType
{
    const FALSE_TYPE   = 'fileExcludeMimeTypeFalse';
    const NOT_DETECTED = 'fileExcludeMimeTypeNotDetected';
    const NOT_READABLE = 'fileExcludeMimeTypeNotReadable';

    /**#@+
     * API restored by Taiwen Jiang
     */
    /**
     * Returns true if the mimetype of the file does not matche the given ones. Also parts
     * of mimetypes can be checked. If you give for example "image" all image
     * mime types will not be accepted like "image/gif", "image/jpeg" and so on.
     *
     * @param  string|array $value Real file to check for mimetype
     * @param  array  $file  File data from \Zend\File\Transfer\Transfer
     * @return bool
     */
    public function isValid($value, $file = null)
    {
        if (is_array($value)) {
            if (!isset($value['tmp_name']) || !isset($value['name']) || !isset($value['type'])) {
                throw new Exception\InvalidArgumentException(
                    'Value array must be in $_FILES format'
                );
            }
            $file     = $value['tmp_name'];
            $filename = $value['name'];
            $filetype = $value['type'];
        } elseif (is_array($file)) {
            $filename = $file['name'];
            $filetype = $file['type'];
            $file     = $value;
        } else {
            $file     = $value;
            $filename = basename($file);
            $filetype = null;
        }
        $this->setValue($filename);

        // Is file readable ?
        if (false === stream_resolve_include_path($file)) {
            $this->error(self::NOT_READABLE);
            return false;
        }

        $mimefile = $this->getMagicFile();
        if (class_exists('finfo', false)) {
            $const = defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME;
            if (!$this->isMagicFileDisabled() && (!empty($mimefile) && empty($this->finfo))) {
                $this->finfo = finfo_open($const, $mimefile);
            }

            if (empty($this->finfo)) {
                $this->finfo = finfo_open($const);
            }

            $this->type = null;
            if (!empty($this->finfo)) {
                $this->type = finfo_file($this->finfo, $file);
            }
        }

        if (empty($this->type) &&
            (function_exists('mime_content_type') && ini_get('mime_magic.magicfile'))
        ) {
            $this->type = mime_content_type($file);
        }

        if (empty($this->type) && $this->getHeaderCheck()) {
            $this->type = $filetype;
        }

        if (empty($this->type)) {
            $this->error(self::NOT_DETECTED);
            false;
        }

        $mimetype = $this->getMimeType(true);
        if (in_array($this->type, $mimetype)) {
            $this->error(self::FALSE_TYPE);
            return false;
        }

        $types = explode('/', $this->type);
        $types = array_merge($types, explode('-', $this->type));
        $types = array_merge($types, explode(';', $this->type));
        foreach ($mimetype as $mime) {
            if (in_array($mime, $types)) {
                $this->error(self::FALSE_TYPE);
                return false;
            }
        }

        return true;
    }
    /**#@-*/
}