<?php

use Slim\Views\PhpRenderer;
use EcclesiaCRM\PersonQuery;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use EcclesiaCRM\dto\Notification;
use EcclesiaCRM\dto\Photo;


    $app->get('/', function ($request, $response, $args) use ($app) {
        $renderer = new PhpRenderer("templates/kioskDevices/");
        $pageObjects = array("sRootPath" => $_SESSION['sRootPath']);
        return $renderer->render($response, "sunday-school-class-view.php", $pageObjects);
    });

    $app->get('/heartbeat', function ($request, $response, $args) use ($app) {
        if ( is_null ($app->kiosk) ) {
            return array(
                "Accepted"=>"no",
                "Name"=>"",
                "Assignment"=>"",
                "Commands"=>""
            );
        }

        return json_encode($app->kiosk->heartbeat());
    });

    $app->post('/checkin', function ($request, $response, $args) use ($app) {

        $input = (object)$request->getParsedBody();
        $status = $app->kiosk->getActiveAssignment()->getEvent()->checkInPerson($input->PersonId);
        return $response->withJSON($status);
    });

    $app->post('/uncheckin', function ($request, $response, $args) use ($app) {

        $input = (object)$request->getParsedBody();
        $status = $app->kiosk->getActiveAssignment()->getEvent()->unCheckInPerson($input->PersonId);
        return $response->withJSON($status);
    });

    $app->post('/checkout', function ($request, $response, $args) use ($app) {
        $input = (object)$request->getParsedBody();
        $status = $app->kiosk->getActiveAssignment()->getEvent()->checkOutPerson($input->PersonId);
        return $response->withJSON($status);
    });

    $app->post('/uncheckout', function ($request, $response, $args) use ($app) {
        $input = (object)$request->getParsedBody();
        $status = $app->kiosk->getActiveAssignment()->getEvent()->unCheckOutPerson($input->PersonId);
        return $response->withJSON($status);
    });

    $app->post('/triggerNotification', function ($request, $response, $args) use ($app) {
        $input = (object)$request->getParsedBody();

        $Person = PersonQuery::create()
            ->findOneById($input->PersonId);

        $Notification = new Notification();
        $Notification->setPerson($Person);
        $Notification->setRecipients($Person->getFamily()->getAdults());
        $Notification->setProjectorText($app->kiosk->getActiveAssignment()->getEvent()->getType() . "-" . $Person->getId());
        $Status = $Notification->send();

        return $response->withJSON($Status);
    });


    $app->get('/activeClassMembers', function ($request, $response, $args) use ($app) {
        $res = $app->kiosk->getActiveAssignment()->getActiveGroupMembers();

        if (!is_null($res)) {
            return $app->kiosk->getActiveAssignment()->getActiveGroupMembers()->toJSON();
        }

        return null;
    });


    $app->get('/activeClassMember/{PersonId}/photo', function (ServerRequestInterface $request, ResponseInterface $response, $args) use ($app) {
        $photo = new Photo("Person", $args['PersonId']);
        return $response->write($photo->getPhotoBytes())->withHeader('Content-type', $photo->getPhotoContentType());
    });


