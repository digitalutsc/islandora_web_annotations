<?php

/**
 * @file
 * Static class to keep functions used across various classes.
 *
 */
class AnnotationUtil
{

    private static $initialized = false;

    private static function initialize()
    {
        if (self::$initialized)
            return;

        self::$initialized = true;
    }

    public static function generateUUID()
    {
        self::initialize();
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

    /**
     * Adds the given file as a datastream to the given object.
     *
     * @param AbstractObject $object
     *   An AbstractObject representing an object within Fedora.
     * @param string $datastream_id
     *   The datastream id of the added datastream.
     * @param string $file_uri
     *   A URI to the file containing the content for the datastream.
     *
     * @return array
     *   An array describing the outcome of the datastream addition.
     */
    public static function add_datastream(AbstractObject $object, $datastream_id, $file_uri) {
        self::initialize();

        try {
            $ingest = !isset($object[$datastream_id]);
            $mime_detector = new MimeDetect();
            if ($ingest) {
                $ds = $object->constructDatastream($datastream_id, "M");
                $ds->label = $datastream_id;
            }
            else {
                $ds = $object[$datastream_id];
            }
            $ds->mimetype = $mime_detector->getMimetype($file_uri);
            $ds->setContentFromFile(drupal_realpath($file_uri));
            if ($ingest) {
                $object->ingestDatastream($ds);
            }
            return array(
                'success' => TRUE,
                'messages' => array(
                    array(
                        'message' => t('Created @dsid derivative for (@pid).'),
                        'message_sub' => array(
                            '@dsid' => $datastream_id,
                            '@pid' => $object->id,
                        ),
                        'type' => 'dsm',
                    ),
                ),
            );
        }
        catch (exception $e) {
            return array(
                'success' => FALSE,
                'messages' => array(
                    array(
                        'message' => t('Islandora Web Annotations failed to add @dsid datastream for @pid. Error message: @message<br/>Stack: @stack'),
                        'message_sub' => array(
                            '@dsid' => $datastream_id,
                            '@pid' => $object->id,
                            '@message' => $e->getmessage(),
                            '@stack' => $e->getTraceAsString(),
                        ),
                        'type' => 'watchdog',
                        'severity' => WATCHDOG_ERROR,
                    ),
                ),
            );
        }
    }

    public static function getPIDfromURL($url) {
        $url = str_replace("%3A",":",$url);
        $url = str_replace("#","",$url);
        $targetPID = substr($url, strrpos($url, '/') + 1);
        return $targetPID;
    }

    /**
     *  Returns datetime as a xsd:dateTime with the UTC timezone expressed as "Z".
     */
    public function utcNow() {
      $now = date("Y-m-d H:i:s");
      $utc_now = new DateTime($now);
      $utc_now->setTimezone(new DateTimeZone('UTC'));
      return $utc_now->format('Y-m-d\TH:i:s\Z');
    }

}
