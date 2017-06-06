<?php
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__.'/settings.php';
    use Google\Spreadsheet\DefaultServiceRequest;
    use Google\Spreadsheet\ServiceRequestFactory;

    putenv('GOOGLE_APPLICATION_CREDENTIALS=' . __DIR__ . CLIENT_SECRET);

    function get_model($lang="en") {
        $client = new Google_Client;
        $client->useApplicationDefaultCredentials();
         
        $client->setScopes(['https://spreadsheets.google.com/feeds']);
         
        if ($client->isAccessTokenExpired()) {
            $client->refreshTokenWithAssertion();
        }
         
        $accessToken = $client->fetchAccessTokenWithAssertion()["access_token"];
        ServiceRequestFactory::setInstance(
            new DefaultServiceRequest($accessToken)
        );
    
        $spreadsheetService = new Google\Spreadsheet\SpreadsheetService();
        $spreadsheetFeed = $spreadsheetService->getSpreadsheetFeed();
    
        // Get our spreadsheet
        $spreadsheet = (new Google\Spreadsheet\SpreadsheetService)->getSpreadsheetFeed()->getById('https://spreadsheets.google.com/feeds/spreadsheets/private/full/' . SPREADSHEET_ID);
    
        // Get the first worksheet (tab)
        $worksheets = $spreadsheet->getWorksheetFeed()->getEntries();
        $worksheet = $worksheets[1];
    
        $listFeed = $worksheet->getListFeed();
         
        $res = array();

        foreach ($listFeed->getEntries() as $entry) {
            $representative = $entry->getValues();

            if ($lang == "ru") {
                $res[] = [
                    "country" => $representative["страна"],
                    "sector" => $representative["сектор"],
                    "title" => $representative["название"],
                    "paid" => $representative["платныйилибесплатный"],
                    "official" => $representative["официальныйнеофициальный"],
                    "comments" => $representative["описаниекомментарии"],
                    "link" => $representative["linkcсылка"],
                ];
            } else {
                $res[] = [
                    "country" => $representative["country"],
                    "sector" => $representative["sector"],
                    "title" => $representative["title"],
                    "paid" => $representative["paidorfree"],
                    "official" => $representative["officialinofficial"],
                    "comments" => $representative["descriptioncomments"],
                    "link" => $representative["linkcсылка"],
                ];
            }
        }

        return $res;
    }
