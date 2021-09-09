<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Exports\WMSPickListExport;
use Maatwebsite\Excel\Facades\Excel;
use DateTime;

class PickListController extends Controller
{
    public function getDataForPickListReport()
    {
        $stationID = "6612"; // 6612 // 6832

        $url = "http://demo.blocworx.local/api/api/v2/get-data-by-scan-station?stationID=$stationID";

        $response = Http::withToken(env('API_TOKEN'))->get($url);

        $data = $response->json();

        return $data;
    }

    public function generateDataForPickListReport($data)
    {
        // Initial setup
        $resultData = [
            'Header' => ['Empty Field' => '', 'Label' => 'Grand Total'],
            'PickedLine' => ['Label' => 'Picked', 'Grand Total' => 0],
            'UnpickedLine' => ['Label' => 'Unpicked', 'Grand Total' => 0],
            'TotalLine' => ['Label' => 'Total', 'Grand Total' => 0]
        ];

        foreach ($data as $row) {

            /**
             * The main idea is the picked is counted based on the updated_at field and the unpicked is counted based on the created_at field
             * 
             * generate the data use the following logic
             * 1. If the item is picked, check if the Header contains updated_at date
             * 2. If the Header does contain the updated_at date, increment the picked line and totals
             * 3. If the Header does not contain the updated_at date, add the updated_at date to the Header and initialize picked, unpicked  and totals if they are not already initialized
             * 4. If the item is not picked, check if the Header contains created_at date
             * 5. If the Header does contain the created_at date, increment the unpicked line and totals
             * 6. If the Header does not contain the created_at date, add the created_at date to the Header and initialize picked, unpicked  and totals if they are not already initialized
             * 
             */

            $entryData = json_decode($row['entry_data'], true);

            $date = new DateTime($row['updated_at']);
            $updateDformatedDate = $date->format('d M y');

            $date = new DateTime($row['created_at']);
            $createDformatedDate = $date->format('d M y');

            if ($entryData['pickedunpicked'] == 'Picked') {

                if (in_array($updateDformatedDate, $resultData['Header'])) {

                    $resultData['PickedLine']['Grand Total'] += 1;
                    $resultData['TotalLine']['Grand Total'] += 1;
                    $resultData['PickedLine'][$updateDformatedDate] += 1;
                    $resultData['TotalLine'][$updateDformatedDate] += 1;
                } else {

                    $resultData['Header'][$updateDformatedDate] = $updateDformatedDate;

                    $resultData['PickedLine']['Grand Total'] += 1;
                    $resultData['TotalLine']['Grand Total'] += 1;

                    empty($resultData['PickedLine'][$updateDformatedDate]) ? $resultData['PickedLine'][$updateDformatedDate] = 1 : $resultData['PickedLine'][$updateDformatedDate] += 1;
                    empty($resultData['UnpickedLine'][$updateDformatedDate]) ? $resultData['UnpickedLine'][$updateDformatedDate] = 0 : $resultData['UnpickedLine'][$updateDformatedDate] += 0;
                    empty($resultData['TotalLine'][$updateDformatedDate]) ? $resultData['TotalLine'][$updateDformatedDate] = 1 : $resultData['TotalLine'][$updateDformatedDate] += 1;
                }
            } else { // entryData is unpicked

                if (in_array($createDformatedDate, $resultData['Header'])) {
                    $resultData['UnpickedLine']['Grand Total'] += 1;
                    $resultData['TotalLine']['Grand Total'] += 1;
                    $resultData['UnpickedLine'][$createDformatedDate] += 1;
                    $resultData['TotalLine'][$createDformatedDate] += 1;
                } else {
                    $resultData['Header'][$createDformatedDate] = $createDformatedDate;

                    $resultData['UnpickedLine']['Grand Total'] += 1;
                    $resultData['TotalLine']['Grand Total'] += 1;

                    empty($resultData['PickedLine'][$createDformatedDate]) ? $resultData['PickedLine'][$createDformatedDate] = 0 : $resultData['PickedLine'][$createDformatedDate] += 0;
                    empty($resultData['UnpickedLine'][$createDformatedDate]) ? $resultData['UnpickedLine'][$createDformatedDate] = 1 : $resultData['UnpickedLine'][$createDformatedDate] += 1;
                    empty($resultData['TotalLine'][$createDformatedDate]) ? $resultData['TotalLine'][$createDformatedDate] = 1 : $resultData['TotalLine'][$createDformatedDate] += 1;
                }
            }
        }

        return $resultData;
    }

    public function generatePickListReport()
    {
        try {

            $data = $this->getDataForPickListReport();

            $dataForReport = $this->generateDataForPickListReport($data);

            $collection = collect($dataForReport);

            Excel::store(new WMSPickListExport($collection), "/exports/Eir-Reserved-Pick-List.xlsx");
        } catch (\Exception $exception) {

            return response()->json([
                'error' => $exception->getMessage(),
                'Line' => $exception->getLine(),
                'File' => $exception->getFile(),
                'Trace' => $exception->getTraceAsString(),
            ], 500);
        }
        return response()->json([$dataForReport], 200);

        return response()->json(['Status' => 'Success'], 200);
    }
}
