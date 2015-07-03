<?php

namespace Utils;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\MvcEvent;
use Zend\Mail;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Message as MimeMessage;
class CommonUtils extends AbstractActionController
{
    /**
     * @function        = formErrorHandler()
     * @param     		= accept the array parameter variable
     * @description     = used to handle the form errors
     */
    public function formErrorHandler($array, $class = 'error')
    {

        if (is_array($array)) {

            // flashMessenger is used to populate any kind of message here is create a message
            // we can use the message in the view renderer to populate and error.

        	$this->flashMessenger()->setNamespace($class)->addMessage('Please Fill Required * Fields.');

        	foreach ($array as $key => $value) {

    			if (is_array($value)) {

    				foreach ($value as $keyval => $innerval) {
						/**
						 * @function       = converToLable()
						 * @param     	   = accept error message lable for
						 * @description    = convert to uppercase words
						 */

    					$this->flashMessenger()->setNamespace($class)->addMessage( $this->converToLable($key) . $innerval );
    				}
        		}
                else{
                    $this->flashMessenger()->setNamespace($class)->addMessage( $this->converToLable('Uploaded File') . $value );
                }
        	}

        } else {
        	 throw new \Exception('Invalid parameter array is required.');
        }

    }



    /**
     * @function        = setFormPreserve()
     * @param           = accept the array parameter form data
     * @description     = used to set or preserve form data
     */
    public function setFormPreserve($array)
    {
        $session = new \Zend\Session\Container('formData');
        $session->formData = $array;

    }

    /**
     * @function        = getFormPreserve()
     * @description     = used get form data preserved
     */
    public function getFormPreserve()
    {
        $session = new \Zend\Session\Container('formData');
        $formData = $session->formData;
        $session->formData = '';
        return $formData;
    }

	/**
	 * @function       = converToLable()
	 * @param     	   = accept error message lable like zone_code
	 * @description    = convert to uppercase words like Zone Code
	 */

    public function converToLable($value = '')
    {
    	return ucwords( str_replace('_', ' ', $value) ) . ': ';
    }

    /**
     * @function       = renderErrors()
     * @param          = accept error message Namespace allready that allready we create for errors
     * @return array() = return array of errors that is available in Namespace
     */
    public function renderErrors($error = 'error'){

        if ($this->flashMessenger()->setNamespace($error)->hasMessages()) {
            return $this->flashMessenger()->setNamespace($error)->getMessages();
            $this->flashMessenger()->clearMessages();
        }

    }

    /**
     * @function       = dataTableOrder()
     * @param          = accept 2 params
     *                   1. datatable 'params' onpage load send by datatable 'array'
     *                   2. table columns that we have to find order by 'array'
     * @return         = return order string
     */
    public function dataTableOrder($dataTableParams, $tableColumns){

        /*
         * Datatable Ordering
         */
        if ( isset( $dataTableParams['iSortCol_0'] ) )
        {
            //create empty string for order
            $sOrder = '';
            for ( $i = 0 ; $i < intval($dataTableParams['iSortingCols']) ; $i++ )
            {
                if ( $dataTableParams[ 'bSortable_'.intval($dataTableParams['iSortCol_'.$i]) ] == 'true' )
                {
                    $sOrder .= $tableColumns[ intval( $dataTableParams['iSortCol_'.$i] ) ].
                        ($dataTableParams['sSortDir_'.$i]==='asc' ? ' ASC' : ' DESC') ;
                }
            }
            return $sOrder;
        }
    }

    /**
     * @function       = dataTableWhere()
     * @param          = accept 2 params
     *                   1. datatable 'params' onpage load send by datatable 'array'
     *                   2. table columns that we have to find order by 'array'
     * @return         = return order string
     */
    public function dataTableWhere($dataTableParams, $tableColumns) {

        /*
         * Datatable Filtering
         * NOTE this does not match the built-in DataTables filtering which does it
         * word by word on any field. It's possible to do here, but concerned about efficiency
         * on very large tables, and MySQL's regex functionality is very limited
         */

        $sWhere = '';
        if ( isset($dataTableParams['sSearch']) && $dataTableParams['sSearch'] != "" )
        {
            // start from 1 not id include means zone_name and zone_code
            // set empty array for where clause.
            $sWhere = array();
            $init = 1;
            if ($tableColumns[0] != 'id') {
                $init = 0;
            }

            for ( $i=$init; $i<count($tableColumns); $i++ )
            {
                $sWhere[$tableColumns[$i]] = '%' . trim(addslashes($dataTableParams['sSearch'])) .'%';
            }
            return $sWhere;
        }

        /* Individual column filtering currently not used can be used in future */
        /*for ( $i=0 ; $i<count($tableColumns) ; $i++ )
        {
            if ( isset($dataTableParams['bSearchable_'.$i]) && $dataTableParams['bSearchable_'.$i] == "true" && $dataTableParams['sSearch_'.$i] != '' )
            {
                if ( $sWhere == "" )
                {
                    $sWhere = "WHERE ";
                }
                else
                {
                    $sWhere .= " AND ";
                }
                $sWhere .= "`".$tableColumns[$i]."` LIKE '%".mysql_real_escape_string($dataTableParams['sSearch_'.$i])."%' ";
            }
        }*/
    }

    /**
     * @function       = dataTableDataRenderer()
     * @param          = accept 4 params 2, 3 are same so i count as 1
     *                   1. 'sEcho' param onpage load send by datatable intval param 'sEcho'
     *                   2. 'iTotal' total records found in the table
     *                   3. 'iTotalDisplayRecords' total records found in the table
     *                   4. 'aaData' data set for datatable render records accept 'resultSet'
     *                   5. self::ID is the table primary key field used for manipulation DML
     *                      statements edit and delete.
     * @return         = return json format data
     */
    public function dataTableDataRenderer($page, $iTotal, $resultSet, $primaryId = '')
    {
            // create empty array set for render data
            $dataSet = array();
            // set start for the render array to datatable
            $j = 0;
            //outer key start from 0 reseultset key auto increment from 0 and value pairs

            foreach ($resultSet->toArray() as $outerKey => $value) {
                foreach ($value as $key => $data)  {

                    if ($key == $primaryId)   //for find out Sr No.
                        $dataSet[$j][] = ($outerKey+1);
                    else
                        $dataSet[$j][] = $data;
                }
                //pass primary key to data for editing and deletion operations and etc.
                if (trim($primaryId) <> '') {
                    $dataSet[$j][] = $value[$primaryId];
                }

                $j++;
            }

            $dataTableRenderData = array(
                        'sEcho' => intval($page),
                        'iTotalRecords' => $iTotal,
                        'iTotalDisplayRecords' => $iTotal,
                        'aaData' => $dataSet
                    );

        return json_encode( $dataTableRenderData );
    }

    /**
     * @return array
     * @descrption return months
     */
    public function getMonths()
    {
        $months = [
            '01' => 'Jan',
            '02' => 'Feb',
            '03' => 'Mar',
            '04' => 'Apr',
            '05' => 'May',
            '06' => 'Jun',
            '07' => 'Jul',
            '08' => 'Aug',
            '09' => 'Sept',
            '10' => 'Oct',
            '11' => 'Nov',
            '12' => 'Dec',
        ];
        return $months;
    }

    /**
     * @return Amount into words
     * @descrption return string
     */
    public function convert_number_to_words($number) 
    {

    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $negative    = 'negative ';
    $decimal     = ' point ';
    $dictionary  = array(
        0                   => 'zero',
        1                   => 'one',
        2                   => 'two',
        3                   => 'three',
        4                   => 'four',
        5                   => 'five',
        6                   => 'six',
        7                   => 'seven',
        8                   => 'eight',
        9                   => 'nine',
        10                  => 'ten',
        11                  => 'eleven',
        12                  => 'twelve',
        13                  => 'thirteen',
        14                  => 'fourteen',
        15                  => 'fifteen',
        16                  => 'sixteen',
        17                  => 'seventeen',
        18                  => 'eighteen',
        19                  => 'nineteen',
        20                  => 'twenty',
        30                  => 'thirty',
        40                  => 'fourty',
        50                  => 'fifty',
        60                  => 'sixty',
        70                  => 'seventy',
        80                  => 'eighty',
        90                  => 'ninety',
        100                 => 'hundred',
        1000                => 'thousand',
        1000000             => 'million',
        1000000000          => 'billion',
        1000000000000       => 'trillion',
        1000000000000000    => 'quadrillion',
        1000000000000000000 => 'quintillion'
    );

    if (!is_numeric($number)) {
        return false;
    }

    if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
        // overflow
        trigger_error(
            'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
            E_USER_WARNING
        );
        return false;
    }

    if ($number < 0) {
        return $negative . $this->convert_number_to_words(abs($number));
    }

    $string = $fraction = null;

    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }

    switch (true) {
        case $number < 21:
            $string = $dictionary[$number];
            break;
        case $number < 100:
            $tens   = ((int) ($number / 10)) * 10;
            $units  = $number % 10;
            $string = $dictionary[$tens];
            if ($units) {
                $string .= ' ' . $dictionary[$units];
            }
            break;
        case $number < 1000:
            $hundreds  = $number / 100;
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) {
                $string .= $conjunction . $this->convert_number_to_words($remainder);
            }
            break;
        default:
            $baseUnit = pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = $this->convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= $this->convert_number_to_words($remainder);
            }
            break;
    }

    if (null !== $fraction && is_numeric($fraction)) {
        $string .= $decimal;
        $words = array();
        foreach (str_split((string) $fraction) as $number) {
            $words[] = $dictionary[$number];
        }
        $string .= implode(' ', $words);
    }

    return $string;
    }


    public function getRandomNumericNumber()
    {

        $data = '0123456789';
        $splitToArray = str_split($data, 1);
        $randomNumber = 0;
        for($i=0; $i<3; $i++)
        {   
            $randomNumber .= $splitToArray[rand(0,9)];
        }
        
        return $randomNumber;
    }
    
    /**
     * Method Generates api response array
     *
     * @param int $statusCode
     * @param string$message
     * @param array $data
     * @return array
     */
    public function encodeJson($statusCode, $message, $data = [])
    {
        $status = $statusCode == 200 ? true : false;
        $jsonArray = [
            'success' => $status,
            'code' => $statusCode,
            'message' => $message,
        ];
        $jsonArray += $data;

        return $jsonArray;
    }
   /* public function sendMail()
    {
        $mail = new Mail\Transport\SmtpOptions(array(
            'name' => 'localhost',
            'host' => 'smtp.gmail.com',
            'port'=> 587,
            'connection_class' => 'login',
            'connection_config' => array(
                'username' => 'email.new.tester2014@gmail.com',
                'password' => '!@#tester$%^',
                'ssl'=> 'tls',
            ),
        ));
        return $mail;
    }*/
}

