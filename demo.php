<?php
    require_once("ICal.class.php");
    
    // Configure the ical instance
    $config = array(
        "productId" => "MyConference - Online Registry",
        "eventIdPrefix" => "myevent-",
        "eventCreationDate" => date("Y-m-d H:i:s", time()),
        "organizerName" => "The organizer",
        "organizerEmail" => "the-organizer@online-registry.net",
    );

    // Define events to be exported
    $events = array(
        array(
            "eventId" => "my-event-1",
            "name" => "My event",
            "description" => "This is my custom iCal event.",
            "location" => "At work",
            "startDateTime" => date("Y-m-d H:i:s", time()),
            "endDateTime" => date("Y-m-d H:i:s", time() + 60 * 60)
        )
    );
    
    $exportFilename = "/tmp/export.ical";

    // Export events and review results
    $ical = new ICal($config);
    
    foreach($events as $entry) {
        $ical->appendEvent($entry['eventId'], $entry['name'], $entry['description'], $entry['location'], $entry['startDateTime'], $entry['endDateTime']);
    }
    
    echo $ical->toString();

    if ($ical->save($exportFilename)) {
        echo sprintf("File %s successfully exported.\n", $exportFilename);
    } else {
        echo sprintf("Could not export file %s. Please check your file permissions and try it again.\n", $exportFilename);
    }
?>
