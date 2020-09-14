<?php

class FeedParser
{

    private $a_sortedData = [];

    const FILTER_AMOUNT = 0.75;

    const DATA_FEED_1 = 'http://www.illman.net/data.txt';

    public function processFeed()
    {
        $rawData = file_get_contents(self::DATA_FEED_1);
        if (! empty($rawData)) {
            $this->parseRows($rawData);
            $this->sortArray();
        }
    }

    public function buildTable()
    {
        $table = '<table class="table">
  <thead>
    <tr>
      <th scope="col">Name</th>
      <th scope="col">Total Price</th>
      <th scope="col">Amount</th>
    </tr>
  </thead>
  <tbody>';
        foreach ($this->a_sortedData as $key => $val) {
            $table .= '<tr>';
            $table .= '<td>' . $val['name'] . '</td>';
            $table .= '<td>$' . $val['totalPrice'] . '</td>';
            $table .= '<td>' . $val['amount'] . 'kg</td>';
            $table .= '</tr>';
        }

        $table .= '</tbody></table>';

        return $table;
    }

    private function parseRows($rawData)
    {
        // parse by EOL
        $a_fileContent = preg_split("/\n/", $rawData);
        // Skip header rows
        $headerRows = 2;

        $count = 0;
        foreach ($a_fileContent as $fileContent) {
            if (! empty($fileContent) && $count >= $headerRows) {
                $this->parseColumns($fileContent);
            }
            $count ++;
        }
    }

    private function parseColumns($row)
    {
        $row = preg_replace('/\s+$/', '', $row); // remove EOL spaces
        $row = preg_split('/\s\s\s+/', $row); // delimit column by 3+ spaces
        if (array_search($row[0], array_column($this->a_sortedData, 'name')) === false) {
            $quantity = $this->getStandardizedQuantity($row[1]);
            if ($this->doWeightCheck($quantity)) {
                $totalPrice = $this->calculateTotalPrice($quantity, $row[2]);
                $this->addToArray([
                    'name' => $row[0],
                    'totalPrice' => $totalPrice,
                    'amount' => $quantity
                ]);
            }
        }
    }

    private function addToArray($a_data)
    {
        $this->a_sortedData[] = $a_data;
    }

    private function doWeightCheck($amount)
    {
        if ($amount <= self::FILTER_AMOUNT) {
            return false;
        }
        return true;
    }

    private function calculateTotalPrice($quantity, $unitPrice)
    {
        $numeric = preg_replace('/[^0-9.]/', '', $unitPrice);

        return $numeric * $quantity;
    }

    private function getStandardizedQuantity($quantity)
    {
        $unit = preg_replace('/[0-9.]/', '', $quantity);
        $numericQuantity = preg_replace('/[^0-9.]/', '', $quantity);
        $standardQuantity = '';

        switch (strtolower(trim($unit))) {
            case 'lb':
                $standardQuantity = number_format($numericQuantity / 2.205, 2);
                break;
            case 'g':
                $standardQuantity = $numericQuantity / 1000;
                break;

            default:
                $standardQuantity = $numericQuantity;
                break;
        }
        return $standardQuantity;
    }

    private function sortArray()
    {
        $prices = array_column($this->a_sortedData, 'totalPrice');
        array_multisort($prices, SORT_DESC, $this->a_sortedData);
    }
}

