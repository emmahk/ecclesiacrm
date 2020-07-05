<?php

// Routes
use Slim\Http\Request;
use Slim\Http\Response;

use EcclesiaCRM\SessionUser;

use EcclesiaCRM\PersonLastMeeting;
use EcclesiaCRM\PersonLastMeetingQuery;
use EcclesiaCRM\PersonMeetingQuery;
use EcclesiaCRM\PersonMeeting;


$app->group('/meeting', function () {
    $this->get('/', 'getAllMettings');
    $this->get('/getLastMeeting', 'getLastMeeting');
    $this->post('/createMeetingRoom', 'createMeetingRoom');
    $this->post('/selectMeetingRoom', 'selectMeetingRoom');
    $this->delete('/deleteAllMeetingRooms', 'deleteAllMeetingRooms');
});

function deleteAllMeetingRooms(Request $request, Response $response, array $args)
{
    $personId = SessionUser::getUser()->getPersonId();

    $all_pms = PersonMeetingQuery::create()->findByPersonId($personId);

    if ( !is_null ($all_pms) ) {
        $all_pms->delete();
    }

    echo true;
}

function selectMeetingRoom(Request $request, Response $response, array $args)
{
    $input = (object)$request->getParsedBody();

    if ( isset($input->roomId) ) {
        $personId = SessionUser::getUser()->getPersonId();

        $lpm = PersonLastMeetingQuery::create()->findOneByPersonId($personId);

        if ( is_null($lpm) ) {
            $lpm = new PersonLastMeeting();
        }

        $lpm->setPersonMeetingId($input->roomId);
        $lpm->setPersonId($personId);
        $lpm->save();

        echo $lpm->toJSON();
    }

    echo null;
}

function createMeetingRoom(Request $request, Response $response, array $args)
{
    $input = (object)$request->getParsedBody();

    if ( isset($input->roomName) ) {
        $personId = SessionUser::getUser()->getPersonId();

        $pm = new PersonMeeting();
        $pm->setCode($input->roomName);
        $pm->setPersonId($personId);

        $date = new DateTime('now');
        $pm->setCreationDate($date->format('Y-m-d h:m'));
        $pm->save();

        $lpm = PersonLastMeetingQuery::create()->findOneByPersonId($personId);

        if ( is_null($lpm) ) {
            $lpm = new PersonLastMeeting();
        }

        $lpm->setPersonMeetingId($pm->getId());
        $lpm->setPersonId($personId);
        $lpm->save();

        echo $pm->toJSON();
    }

    echo null;
}

function getLastMeeting(Request $request, Response $response, array $args)
{
    $personId = SessionUser::getUser()->getPersonId();

    $lpm = PersonLastMeetingQuery::create()->findOneByPersonId($personId);

    if (!is_null($lpm)) {
        $pm = PersonMeetingQuery::create()->findOneById($lpm->getPersonMeetingId());
        echo $pm->toJSON();
    } else {
        echo null;
    }
}

function getAllMettings(Request $request, Response $response, array $args)
{
    $personId = SessionUser::getUser()->getPersonId();

    $meetings = PersonMeetingQuery::create()
        ->findByPersonId($personId);

    echo $meetings->toJSON();
}

