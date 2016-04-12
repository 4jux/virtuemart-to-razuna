<?php
/**
 * Copyright (c) 2016.
 * Creator: Jaanus Aomere
 */


$image_url = "http://virtuemart.site.com/components/com_virtuemart/shop_image/product/";
$api_key = "";
$folder_id = "C3AD0815CF3542C9A598730CFD264A0A";
$file = fopen('3column_img_list.csv','r');
$url = "http://localhost:8888/razuna/raz2/dam/index.cfm?";
$label_url = "http://localhost:8888/razuna//global/api2/label.cfc?";
$search_url = "http://localhost:8888/razuna//global/api2/search.cfc?";
$counter = 0;

ob_start();
while(($row = fgetcsv($file, 0, ";")) !== FALSE) {

    //lae alla pilt

    if (empty($row[1]) && empty($row[2])) {
        $pilt_dest = '';
    }
    elseif (!empty($row[1]) && !empty($row[2])) {
        $pilt = $image_url . $row[2];
        copy($pilt,'upload/'.$row[2]);
        if (!copy($pilt,'upload/'.$row[2])) {
            $pilt_dest = '';
        } else {
            $pilt_dest = 'upload/'.$row[2];
            $picture_explode = explode("/",$row[2]);
            $picture_name = $picture_explode[0];
        }
    }
    elseif (!empty($row[1]) && empty($row[2])) {
        $pilt = $image_url . $row[1];
        copy($pilt,'upload/'.$row[1]);
        if(!copy($pilt,'upload/'.$row[1])){
            $pilt_dest = '';
        } else {
            $pilt_dest = 'upload/'.$row[1];
            $picture_explode = explode("/",$row[1]);
            $picture_name = $picture_explode[0];

        }
    }
    elseif (empty($row[1]) && !empty($row[2])) {
        $pilt = $image_url . $row[2];
        copy($pilt,'upload/'.$row[2]);
        if(!copy($pilt,'upload/'.$row[2])) {
            $pilt_dest = '';
        } else {
            $pilt_dest = 'upload/'.$row[2];
            $picture_explode = explode("/",$row[2]);
            $picture_name = $picture_explode[0];
        }
    }

    /*elseif (!empty($row[2])) {
        if (!copy($pilt,'upload/'.$row[1]) && !copy($pilt,'upload/'.$row[2])) {
            $pilt_dest = '';
        }
        elseif (!copy($pilt,'upload/'.$row[1]) && copy($pilt,'upload/'.$row[2])) {
            $pilt_dest = 'upload/'.$row[2];
        }
        elseif (copy($pilt,'upload/'.$row[1]) && !copy($pilt,'upload/'.$row[2])) {
            $pilt_dest = 'upload/'.$row[1];
        }
        elseif(copy($pilt,'upload/'.$row[1]) && copy($pilt,'upload/'.$row[2])) {
            $pilt_dest = 'upload/'.$row[2];
        }
    }*/
    //copy($pilt,'upload/'.$row[2]);

    
    $curl_file_upload = new CURLFile($pilt_dest);
    if (empty($pilt_dest)) {
        echo "Picture not existing: Doing nothing\n\n";
        }
    else {
        $counter++;
        //otsi label ja saa labeli id
        $search_label = curl_init();
        curl_setopt($search_label, CURLOPT_URL, $label_url);
        curl_setopt($search_label, CURLOPT_POST, true);
        curl_setopt($search_label, CURLOPT_POSTFIELDS, array(
            'method' => "searchlabel",
            'api_key' => $api_key,
            'searchfor' => $row[0],
        ));
        curl_setopt($search_label, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($search_label, CURLOPT_HEADER, 0);
        $search_label_execute = curl_exec($search_label);
        ob_flush();
        curl_close($search_label);
        $decode = json_decode($search_label_execute);

        //kui tühi siis sisesta väärtuseks null
        if (empty($decode->DATA)) {
            $label_id = '';
            echo "Did not found label continuing... \n";
        } //kui väärtus on olemas siis kontrolli üle saadud väärtused ja võta label id väärtus
        else {
            foreach ($decode->DATA as $label) {
                if ($label[1] === $row[0]) {
                    echo "Found existing label: $row[0]\n";
                    $label_id = $label[0];
                }
            }
        }

        //otsi, kas pilt on eelnevalt olemas
        $search_picture = curl_init();
        curl_setopt($search_picture, CURLOPT_URL, $search_url);
        curl_setopt($search_picture, CURLOPT_POST, true);
        curl_setopt($search_picture, CURLOPT_POSTFIELDS, array(
            'method' => "searchassets",
            'api_key' => $api_key,
            'searchfor' => 'filename:("' . $picture_name . '")',
        ));
        curl_setopt($search_picture, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($search_picture, CURLOPT_HEADER, 0);
        $search_picture_execute = curl_exec($search_picture);
        ob_flush();
        curl_close($search_picture);
        $picture_decode = json_decode($search_picture_execute);

        if (empty($picture_decode->DATA)) {
            $picture_id = '';
            echo "Did not found picture continuing... \n";
        } else {
            foreach ($picture_decode->DATA as $picture) {
                if ($picture[1] === $picture_name) {
                    echo "Found existing picture: $picture_name\n";
                    $picture_id = $picture[0];
                }
            }
        }

        //vaadata kas label on pildile omistatud juba
        if (!empty($picture_id)) {

            $search_picture_label = curl_init();
            curl_setopt($search_picture_label, CURLOPT_URL, $label_url);
            curl_setopt($search_picture_label, CURLOPT_POST, true);
            curl_setopt($search_picture_label, CURLOPT_POSTFIELDS, array(
                'method' => "getlabelofasset",
                'api_key' => $api_key,
                'asset_id' => $picture_id,
                'asset_type' => 'img',
            ));
            curl_setopt($search_picture_label, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($search_picture_label, CURLOPT_HEADER, 0);
            $search_picture_label_execute = curl_exec($search_picture_label);
            ob_flush();
            curl_close($search_picture_label);
            $search_picture_label_decode = json_decode($search_picture_label_execute);

            //kui pildi otsingus label väljad ei ole tühjad ja on olemas label_id
            elseif (!empty($search_picture_label_decode->DATA) && !empty($label_id)) {
                $label_exists = FALSE;
                foreach ($search_picture_label_decode->DATA as $label_picture) {
                    if ($label_picture[1] === $label_id) {
                        echo "Picture alredy has right label added: $picture_name\n";
                        $label_exists = TRUE;
                    }
                }
                //Kui pildi juurest ei leia labelit siis lisa label juurde olemasolevale.
                if ($label_exists == FALSE) {
                    $add_picture_label = curl_init();
                    curl_setopt($add_picture_label, CURLOPT_URL, $label_url);
                    curl_setopt($add_picture_label, CURLOPT_POST, true);
                    curl_setopt($add_picture_label, CURLOPT_POSTFIELDS, array(
                        'method' => "setassetlabel",
                        'api_key' => $api_key,
                        'label_id' => $label_id,
                        'asset_id' => $picture_id,
                        'asset_type' => 'img',
                        'append' => 'true',
                    ));
                    curl_setopt($add_picture_label, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($add_picture_label, CURLOPT_HEADER, 0);
                    $add_picture_label_execute = curl_exec($add_picture_label);
                    ob_flush();
                    curl_close($add_picture_label);
                }
            }
        }




        //kui label ei ole olemas, siis sisesta uus ja saa labeli id, sisesta uus pilt, kui pole olmas
        if (empty($label_id) && empty($picture_id)) {
            echo "Uploading picture and creating new lable\n";

            ///Pildi üles laadimine ja assetid saamine


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_HEADER, "Expect: ");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                'fa' => 'c.apiupload',
                'api_key' => $api_key,
                'destfolderid' => $folder_id,
                'filedata' => $curl_file_upload,
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $ch_execute = curl_exec($ch);
            ob_flush();
            curl_close($ch);

            $file_decode = new SimpleXMLElement($ch_execute);

            $picture_id = $file_decode->assetid;

            //uue labeli loomine
            $create_label = curl_init();
            curl_setopt($create_label, CURLOPT_URL, $label_url);
            curl_setopt($create_label, CURLOPT_POST, true);
            curl_setopt($create_label, CURLOPT_POSTFIELDS, array(
                'method' => "setlabel",
                'api_key' => $api_key,
                'label_text' => $row[0],
            ));
            curl_setopt($create_label, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($create_label, CURLOPT_HEADER, 0);
            $create_label_execute = curl_exec($create_label);
            ob_flush();
            curl_close($create_label);
            $create_label_decode = json_decode($create_label_execute);

            $label_id = $create_label_decode->label_id;

            //lisa label pildile
            echo "Adding labels to picture\n\n";
            $add_label_to_picture = curl_init();
            curl_setopt($add_label_to_picture, CURLOPT_URL, $label_url);
            curl_setopt($add_label_to_picture, CURLOPT_POST, true);
            curl_setopt($add_label_to_picture, CURLOPT_POSTFIELDS, array(
                'method' => "setassetlabel",
                'api_key' => $api_key,
                'label_id' => "94265503A8684979AD611575899DEF97,328B6D6080FA48308F300EBB13141B1C,$label_id",
                'asset_id' => $picture_id,
                'asset_type' => 'img',
            ));
            curl_setopt($add_label_to_picture, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($add_label_to_picture, CURLOPT_HEADER, 0);
            $add_label_to_picture_execute = curl_exec($add_label_to_picture);
            ob_flush();
            curl_close($add_label_to_picture);

        } //kui on label olamas, kuid pilt puudu siis sisesta uus pilt ja seo labeliga
        elseif (!empty($label_id) && empty($picture_id)) {

            echo "Missing picture: adding picture\n";

            ///Pildi üles laadimine ja assetid saamine

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_VERBOSE, 0);
            curl_setopt($ch, CURLOPT_HEADER, "Expect: ");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, array(
                'fa' => 'c.apiupload',
                'api_key' => $api_key,
                'destfolderid' => $folder_id,
                'filedata' => $curl_file_upload,
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            $ch_execute = curl_exec($ch);
            ob_flush();
            curl_close($ch);

            $file_decode = new SimpleXMLElement($ch_execute);

            $picture_id = $file_decode->assetid;

            //lisa label pildile
            echo "Adding labels to picture\n\n";
            $add_label_to_picture = curl_init();
            curl_setopt($add_label_to_picture, CURLOPT_URL, $label_url);
            curl_setopt($add_label_to_picture, CURLOPT_POST, true);
            curl_setopt($add_label_to_picture, CURLOPT_POSTFIELDS, array(
                'method' => "setassetlabel",
                'api_key' => $api_key,
                'label_id' => "94265503A8684979AD611575899DEF97,328B6D6080FA48308F300EBB13141B1C,$label_id",
                'asset_id' => $picture_id,
                'asset_type' => 'img',
            ));
            curl_setopt($add_label_to_picture, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($add_label_to_picture, CURLOPT_HEADER, 0);
            $add_label_to_picture_execute = curl_exec($add_label_to_picture);
            ob_flush();
            curl_close($add_label_to_picture);
        } //kui pilt olemas, kuid label puudu siis sisesta uus label ja seo pildiga
        elseif (empty($label_id) && !empty($picture_id)) {

            echo "Missing label: Adding label\n";
            //uue labeli loomine
            $create_label = curl_init();
            curl_setopt($create_label, CURLOPT_URL, $label_url);
            curl_setopt($create_label, CURLOPT_POST, true);
            curl_setopt($create_label, CURLOPT_POSTFIELDS, array(
                'method' => "setlabel",
                'api_key' => $api_key,
                'label_text' => $row[0],
            ));
            curl_setopt($create_label, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($create_label, CURLOPT_HEADER, 0);
            $create_label_execute = curl_exec($create_label);
            ob_flush();
            curl_close($create_label);
            $create_label_decode = json_decode($create_label_execute);

            $label_id = $create_label_decode->label_id;

            //lisa label pildile
            echo "Adding labels to picture\n\n";
            $add_label_to_picture = curl_init();
            curl_setopt($add_label_to_picture, CURLOPT_URL, $label_url);
            curl_setopt($add_label_to_picture, CURLOPT_POST, true);
            curl_setopt($add_label_to_picture, CURLOPT_POSTFIELDS, array(
                'method' => "setassetlabel",
                'api_key' => $api_key,
                'label_id' => "94265503A8684979AD611575899DEF97,328B6D6080FA48308F300EBB13141B1C,$label_id",
                'asset_id' => $picture_id,
                'asset_type' => 'img',
            ));
            curl_setopt($add_label_to_picture, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($add_label_to_picture, CURLOPT_HEADER, 0);
            $add_label_to_picture_execute = curl_exec($add_label_to_picture);
            ob_flush();
            curl_close($add_label_to_picture);
        } //kui label on olemas ja pilt ka siis ära tee midagi
        else {
            echo "Label and picture exist: doing nothing\n\n";
        }
        echo "Picture number: $counter\n\n";
    }
}

ob_end_flush();


