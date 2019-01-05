<?php
/*  TONI TORRES & ALDO MENENDEZ */
	include "dades.php";
	//$_SESSION['pag'] = $pag;

	function connectaBD() {
		global $serverBD;
		global $usuBD;
		global $pwBD;
		global $nomBD;
		
		$conexio = new mysqli($serverBD, $usuBD, $pwBD, $nomBD);
		if ($conexio->connect_errno)
			return false;
		else {
			$conexio->query("SET NAMES 'utf8'");
			return $conexio;
		}
    }
    
    function llistaReserves($inputDate) {
		$inputDate = date('Y-m-d', strtotime($inputDate));
		$elJSON = array();
		$final = array();
		$index = 0;
		$conexio = connectaBD();

		$lasql = "SELECT * FROM booking INNER JOIN room ON room.id_room = booking.id_room";
		
		if($consulta = $conexio->query($lasql)){
			while ($reg = $consulta->fetch_array()) {
				$ini = date('Y-m-d', strtotime($reg['fecha_ini']));
				$end = date('Y-m-d', strtotime($reg['fecha_final']));
				if($inputDate >= $ini && $inputDate <= $end){
					$elJSON[$index]['id_booking'] = $reg['id_booking'];
					$elJSON[$index]['nameof'] = $reg['nameof'];
					$elJSON[$index]['type_booking'] = $reg['type_booking'];
					$elJSON[$index]['estat'] = $reg['estat'];
					$elJSON[$index]['fecha_ini'] = $reg['fecha_ini'];
					$elJSON[$index]['fecha_final'] = $reg['fecha_final'];
					$elJSON[$index]['type_room'] = $reg['type_room'];
					$elJSON[$index]['id_user'] = $reg['id_user'];
					$elJSON[$index]['id_room'] = $reg['id_room'];
					$index++;
				}
			}
			$status = true;
		} else 
			$status = false;
		
		$final['booking'] = $elJSON;
		$final['status'] = $status;
		return json_encode($final);
	}

	function llistaReservesInfo($id_booking_selected) {
		$elJSON = array();
		$final = array();
		$index = 0;
		$conexio = connectaBD();

		$lasql = "SELECT * FROM booking 
		INNER JOIN room ON room.id_room = booking.id_room 
		INNER JOIN customer ON customer.id_customer = booking.id_customer 
		WHERE id_booking = ".$id_booking_selected;
		
		if($consulta = $conexio->query($lasql)){
			while ($reg = $consulta->fetch_array()) {
				$elJSON[$index]['id_booking'] = $reg['id_booking'];
				$elJSON[$index]['nameof'] = $reg['nameof'];
				$elJSON[$index]['num_people'] = $reg['num_people'];
				$elJSON[$index]['type_booking'] = $reg['type_booking'];
				$elJSON[$index]['estat'] = $reg['estat'];
				$elJSON[$index]['fecha_ini'] = $reg['fecha_ini'];
				$elJSON[$index]['fecha_final'] = $reg['fecha_final'];
				$elJSON[$index]['type_room'] = $reg['type_room'];
				$elJSON[$index]['id_user'] = $reg['id_user'];
				$elJSON[$index]['id_room'] = $reg['id_room'];
				$elJSON[$index]['id_customer'] = $reg['id_customer'];
				$elJSON[$index]['name_customer'] = $reg['name_customer'];
				$elJSON[$index]['surname_customer'] = $reg['surname_customer'];
				$elJSON[$index]['dni'] = $reg['dni'];
				$elJSON[$index]['nationality'] = $reg['nationality'];
				$index++;
			}
			$status = true;
		} else 
			$status = false;
		
		$final['booking'] = $elJSON;
		$final['status'] = $status;
		return json_encode($final);
	}

	function eliminarBooking($id_booking_selected){
		$final = array();
		$conexio = connectaBD();

		$lasql = "DELETE FROM booking WHERE id_booking = ".$id_booking_selected;

		if($consulta = $conexio->query($lasql))
			$status = true;
		else
			$status = false;

		$final['status'] = $status;
		return json_encode($final);
	}

	function changeStateBooking($id_booking_selected){
		$final = array();
		$conexio = connectaBD();

		$lasql = "UPDATE booking SET estat = 'ocupat' WHERE id_booking = ".$id_booking_selected;

		if($consulta = $conexio->query($lasql))
			$status = true;
		else
			$status = false;

		$final['status'] = $status;
		return json_encode($final);
	}

	function addBooking($elJSON){
		$final = array();
		$conexio = connectaBD();
		/* Dades */
		$jsonArray = json_decode($elJSON, true);
		extract($jsonArray);
		$estat = "reservat";
		$id_user = 1;
		$option = 0;
		/*function bookingValidation($fecha_ini, $fecha_final, $numRoom, $option = 0, $numHab = "", $id_booking = 0){ */
		if(bookingValidation($fecha_ini, $fecha_final, $numRoom, $option)){
			addCustomer($name_client, $surname_client, $dni, $nacionalidad);
			$id_customer = getCustomerId($dni);

			$lasql = "INSERT INTO booking (nameof, type_booking, estat, fecha_ini, fecha_final, num_people, id_user, id_room, id_customer) VALUES (?,?,?,?,?,?,?,?,?)";
			$consulta = $conexio->prepare($lasql);
			$consulta->bind_param("sssssiiii", $nameof, $type_booking, $estat, $fecha_ini, $fecha_final, $num_people, $id_user, $numRoom, $id_customer);
	
			if ($consulta->execute())
				$status = true;
			else
				$status = false;
		} else {
			$status = "noValid";
		}

		$final['status'] = $status;
		return json_encode($final);
	}

	function getCustomerId($dni){
		$conexio = connectaBD();
		$lasql2 = "SELECT id_customer FROM customer WHERE dni = ?";
		$consulta = $conexio->prepare($lasql2);
		$consulta->bind_param("s", $dni);

		if ($consulta->execute()){
			$result = $consulta->get_result();
			$row = $result->fetch_array();
			$id_customer = $row['id_customer'];
		} else
			$id_customer = "No hay id";

		return $id_customer;
	}

	function getBookingRoom($id_booking){
		$conexio = connectaBD();
		$lasql = "SELECT id_room FROM booking WHERE id_booking = ?";
		$consulta = $conexio->prepare($lasql);
		$consulta->bind_param("i", $id_booking);

		if ($consulta->execute()){
			$result = $consulta->get_result();
			$row = $result->fetch_array();
			$id = $row['id_room'];
		} else
			$id = "No hay id";

		return $id;
	}


	function addCustomer($name_client, $surname_client, $dni, $nacionalidad){
		$conexio = connectaBD();
		$lasql2 = "INSERT INTO customer (name_customer, surname_customer, dni, nationality) VALUES (?,?,?,?)";
		$consulta = $conexio->prepare($lasql2);
		$consulta->bind_param("ssss", $name_client, $surname_client, $dni, $nacionalidad);

		if ($consulta->execute())
			$status = true;
		else
			$status = false;

		return $status;
	}
	
	function bookingValidation($fecha_ini, $fecha_final, $numRoom, $option, $numHab = "", $id_booking = 0){
		$final = array();
		$conexio = connectaBD();
		switch($option){
			case 0: /* En el caso de CREAR una reserva */
				$lasql = "SELECT * FROM booking WHERE id_room = $numRoom
				AND (fecha_ini BETWEEN '".$fecha_ini."' AND '".$fecha_final."'
				OR fecha_final BETWEEN '".$fecha_ini."' AND '".$fecha_final."')";
			break;
			/*case 1:
				$lasql = "SELECT * FROM booking WHERE id_room = $numRoom 
				AND NOT id_room = $numHab
				AND (fecha_ini BETWEEN '".$fecha_ini."' AND '".$fecha_final."'
				OR fecha_final BETWEEN '".$fecha_ini."' AND '".$fecha_final."')";
			break;*/
			case 2: /* En el caso de EDITAR una reserva cambiando el */
				$lasql = "SELECT * FROM booking WHERE id_room = $numRoom 
				AND NOT id_booking = $id_booking
				AND (fecha_ini BETWEEN '".$fecha_ini."' AND '".$fecha_final."'
				OR fecha_final BETWEEN '".$fecha_ini."' AND '".$fecha_final."')";
			break;
		}
		

		//SELECT * FROM booking WHERE id_room = 103
		//AND (fecha_ini BETWEEN '2018-12-30' AND '2018-12-31') 
		//OR (fecha_final BETWEEN '2018-12-30' AND '2018-12-31')";

		if($consulta = $conexio->query($lasql)){
		/* Si no hay resultados significa que no hay conflicto con ningúna otra Reserva
		  con lo cual devuelve true confirmando que puede hacer la reserva sin problemas */
			if ($consulta->num_rows > 0)
				$status = false; 
			else 
				$status = true;
		}else
			$status = "mal";
			
		return $status;
	}

	function updateBooking($elJSON){
		$final = array();
		$conexio = connectaBD();
		/* Dades */
		$jsonArray = json_decode($elJSON, true);
		extract($jsonArray);
		//$estat = "reservat";
		$id_user = 1;
		$currentIdRoom = getBookingRoom($id_booking_selected);

		/* 
		Si la habitación actual es igual a la habitación que ya le correspondia se comparará las fechas sin contar la reserva hecha,
		osea obiando el id de la reserva que editamos, si no daría conflicto siempre que editemos pk existirá una reserva 
		con las mismas fechas a la que intentamos editar 
		*/
		if($currentIdRoom == $numPiso){
			$option = 2;
			if(bookingValidation($date_ini, $date_fi, $numPiso, $option, $numHab = "", $id_booking_selected)){
				$lasql = "UPDATE booking SET nameof=?, type_booking=?, fecha_ini=?, fecha_final=?, num_people=?, id_user=?, id_room=? WHERE id_booking=?";
				$consulta = $conexio->prepare($lasql);
				$consulta->bind_param("ssssiiii", $nameof, $select_type, $date_ini, $date_fi, $num_p, $id_user, $numPiso, $id_booking_selected);
		
				$lasql2 = "UPDATE customer SET name_customer=?, surname_customer=?, dni=?, nationality=? WHERE id_customer=?";
				$consulta2 = $conexio->prepare($lasql2);
				$consulta2->bind_param("ssssi", $customer_name, $customer_surname, $customer_dni, $customer_nacion, $customer_id);
				//$consulta2->execute();
				if ($consulta->execute() && $consulta2->execute())
					$status = true;
				else
					$status = false;

			} else {
				$status = "noValid";
			}
		} else { /* En el caso de que no coincidan se hará el proceso normal de comparar fechas */
			$option = 0;
			if(bookingValidation($date_ini, $date_fi, $numPiso, $option, $currentIdRoom, $id_booking_selected)){
				$lasql = "UPDATE booking SET nameof=?, type_booking=?, fecha_ini=?, fecha_final=?, num_people=?, id_user=?, id_room=? WHERE id_booking=?";
				$consulta = $conexio->prepare($lasql);
				$consulta->bind_param("ssssiiii", $nameof, $select_type, $date_ini, $date_fi, $num_p, $id_user, $numPiso, $id_booking_selected);
		
				$lasql2 = "UPDATE customer SET name_customer=?, surname_customer=?, dni=?, nationality=? WHERE id_customer=?";
				$consulta2 = $conexio->prepare($lasql2);
				$consulta2->bind_param("ssssi", $customer_name, $customer_surname, $customer_dni, $customer_nacion, $customer_id);
				//$consulta2->execute();
				if ($consulta->execute() && $consulta2->execute())
					$status = true;
				else
					$status = false;
			
			} else {
				$status = "noValid";
			}
		}
	
		$final['status'] = $status;
		return json_encode($final);
		
	}


?>