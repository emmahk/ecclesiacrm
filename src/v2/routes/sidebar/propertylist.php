<?php

use Slim\Http\Request;
use Slim\Http\Response;

use EcclesiaCRM\Utils\InputUtils;
use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\utils\RedirectUtils;
use EcclesiaCRM\SessionUser;
use EcclesiaCRM\PropertyTypeQuery;

use Slim\Views\PhpRenderer;

$app->group('/propertylist', function () {
    $this->get('/{type}', 'renderPropertyList');
});


function renderPropertyList (Request $request, Response $response, array $args) {
    $renderer = new PhpRenderer('templates/sidebar/');
    
    if ( !( SessionUser::getUser()->isMenuOptionsEnabled() ) ) {
      return $response->withStatus(302)->withHeader('Location', SystemURLs::getRootPath() . '/Menu.php');
    }
    
    //Get the type to display
    $sType = $args['type'];

    //Based on the type, set the TypeName
    switch ($sType) {
        case 'p':
            $sTypeName = _('Person');
            break;

        case 'f':
            $sTypeName = _('Family');
            break;

        case 'g':
            $sTypeName = _('Group');
            break;

        default:
            RedirectUtils::Redirect('Menu.php');
            exit;
            break;
    }
    
    return $renderer->render($response, 'propertylist.php', argumentsPropertyListArray($sType,$sTypeName));
}

function argumentsPropertyListArray ($sType,$sTypeName)
{
    //Set the page title
    $sPageTitle = _("Property List");

    $sRootDocument  = SystemURLs::getDocumentRoot();
    
    // We need the properties types
    $propertyTypes = PropertyTypeQuery::Create()
                      ->filterByPrtClass($sType)
                      ->find();

          
    $paramsArguments = ['sRootPath'    => SystemURLs::getRootPath(),
                       'sRootDocument' => $sRootDocument,
                       'CSPNonce'      => SystemURLs::getCSPNonce(),
                       'sPageTitle'    => $sPageTitle,
                       'propertyTypes' => $propertyTypes,
                       'sType'         => $sType,
                       'sTypeName'     => $sTypeName,
                       'isMenuOption'  => SessionUser::getUser()->isMenuOptionsEnabled()
                       ];   
   return $paramsArguments;
}