<?php

function hms_pg_save_room_assignment($con, $student_id, $room_number, $bed_label, $floor, $occupancy_type, $rent_amount, $deposit_amount, &$error)
{
    $room_number = trim($room_number);
    $bed_label = trim($bed_label);
    $floor = trim($floor);
    $occupancy_type = strtolower(trim($occupancy_type));
    $capacity_by_type = array('single' => 1, 'double' => 2, 'triple' => 3);

    if ($student_id < 1 || $room_number === '' || strlen($room_number) > 50 ||
        $bed_label === '' || strlen($bed_label) > 10 || $floor === '' || strlen($floor) > 50 ||
        !isset($capacity_by_type[$occupancy_type]) || !is_numeric($rent_amount) || (float)$rent_amount <= 0 ||
        !is_numeric($deposit_amount) || (float)$deposit_amount < 0) {
        $error = "Enter a room number, bed label, floor, single/double/triple occupancy, positive monthly rent, and a valid deposit.";
        return false;
    }

    $room_stmt = mysqli_prepare($con, "SELECT id, floor, occupancy_type, total_beds FROM pg_rooms WHERE room_number = ? FOR UPDATE");
    if (!$room_stmt) {
        error_log("PG room lookup prepare failed: " . mysqli_error($con));
        $error = "Room details could not be checked. Please try again.";
        return false;
    }
    mysqli_stmt_bind_param($room_stmt, "s", $room_number);
    if (!mysqli_stmt_execute($room_stmt)) {
        error_log("PG room lookup failed: " . mysqli_stmt_error($room_stmt));
        mysqli_stmt_close($room_stmt);
        $error = "Room details could not be checked. Please try again.";
        return false;
    }
    mysqli_stmt_bind_result($room_stmt, $room_id, $stored_floor, $stored_occupancy, $room_capacity);
    $room_found = mysqli_stmt_fetch($room_stmt);
    mysqli_stmt_close($room_stmt);

    if ($room_found) {
        $count_stmt = mysqli_prepare($con, "SELECT COUNT(*) FROM pg_beds WHERE room_id = ?");
        if (!$count_stmt) {
            error_log("PG room capacity check prepare failed: " . mysqli_error($con));
            $error = "Room capacity could not be checked. Please try again.";
            return false;
        }
        mysqli_stmt_bind_param($count_stmt, "i", $room_id);
        if (!mysqli_stmt_execute($count_stmt)) {
            error_log("PG room capacity check failed: " . mysqli_stmt_error($count_stmt));
            mysqli_stmt_close($count_stmt);
            $error = "Room capacity could not be checked. Please try again.";
            return false;
        }
        mysqli_stmt_bind_result($count_stmt, $bed_count);
        mysqli_stmt_fetch($count_stmt);
        mysqli_stmt_close($count_stmt);
        $requested_capacity = $capacity_by_type[$occupancy_type];
        if ($bed_count > $requested_capacity) {
            $error = "This room already has more bed records than the selected occupancy allows. Resolve the room's existing beds before reducing its capacity.";
            return false;
        }
        $room_capacity = $requested_capacity;
        $update_room = mysqli_prepare($con, "UPDATE pg_rooms SET floor = ?, occupancy_type = ?, total_beds = ? WHERE id = ?");
        if (!$update_room) {
            error_log("PG room update prepare failed: " . mysqli_error($con));
            $error = "Room details could not be updated. Please try again.";
            return false;
        }
        mysqli_stmt_bind_param($update_room, "ssii", $floor, $occupancy_type, $room_capacity, $room_id);
        $room_updated = mysqli_stmt_execute($update_room);
        if (!$room_updated) {
            error_log("PG room update failed: " . mysqli_stmt_error($update_room));
        }
        mysqli_stmt_close($update_room);
        if (!$room_updated) {
            $error = "Room details could not be updated. Please try again.";
            return false;
        }
    } else {
        $room_capacity = $capacity_by_type[$occupancy_type];
        $insert_room = mysqli_prepare($con, "INSERT INTO pg_rooms (room_number, floor, occupancy_type, total_beds, monthly_rent) VALUES (?, ?, ?, ?, 0)");
        if (!$insert_room) {
            error_log("PG room creation prepare failed: " . mysqli_error($con));
            $error = "Room details could not be saved. Please try again.";
            return false;
        }
        mysqli_stmt_bind_param($insert_room, "sssi", $room_number, $floor, $occupancy_type, $room_capacity);
        $room_created = mysqli_stmt_execute($insert_room);
        if (!$room_created) {
            error_log("PG room creation failed: " . mysqli_stmt_error($insert_room));
        } else {
            $room_id = mysqli_insert_id($con);
        }
        mysqli_stmt_close($insert_room);
        if (!$room_created) {
            $error = "Room details could not be saved. Check whether another warden created this room.";
            return false;
        }
    }

    $bed_stmt = mysqli_prepare($con, "SELECT id, status, student_id FROM pg_beds WHERE room_id = ? AND bed_label = ? LIMIT 1 FOR UPDATE");
    if (!$bed_stmt) {
        error_log("PG bed lookup prepare failed: " . mysqli_error($con));
        $error = "Bed details could not be checked. Please try again.";
        return false;
    }
    mysqli_stmt_bind_param($bed_stmt, "is", $room_id, $bed_label);
    if (!mysqli_stmt_execute($bed_stmt)) {
        error_log("PG bed lookup failed: " . mysqli_stmt_error($bed_stmt));
        mysqli_stmt_close($bed_stmt);
        $error = "Bed details could not be checked. Please try again.";
        return false;
    }
    mysqli_stmt_bind_result($bed_stmt, $bed_id, $bed_status, $bed_student_id);
    $bed_found = mysqli_stmt_fetch($bed_stmt);
    mysqli_stmt_close($bed_stmt);

    if (!$bed_found) {
        $count_stmt = mysqli_prepare($con, "SELECT COUNT(*) FROM pg_beds WHERE room_id = ?");
        if (!$count_stmt) {
            error_log("PG room bed count prepare failed: " . mysqli_error($con));
            $error = "Room capacity could not be checked. Please try again.";
            return false;
        }
        mysqli_stmt_bind_param($count_stmt, "i", $room_id);
        $counted = mysqli_stmt_execute($count_stmt) &&
            mysqli_stmt_bind_result($count_stmt, $bed_count) &&
            mysqli_stmt_fetch($count_stmt);
        if (!$counted) {
            error_log("PG room bed count failed: " . mysqli_stmt_error($count_stmt));
            mysqli_stmt_close($count_stmt);
            $error = "Room capacity could not be checked. Please try again.";
            return false;
        }
        mysqli_stmt_close($count_stmt);
        if ($bed_count >= $room_capacity) {
            $error = "This room already has all of its beds recorded. Enter an existing bed label or update the room capacity.";
            return false;
        }

        $insert_bed = mysqli_prepare($con, "INSERT INTO pg_beds (room_id, bed_label, student_id, status) VALUES (?, ?, NULL, 'vacant')");
        if (!$insert_bed) {
            error_log("PG bed creation prepare failed: " . mysqli_error($con));
            $error = "Bed details could not be saved. Please try again.";
            return false;
        }
        mysqli_stmt_bind_param($insert_bed, "is", $room_id, $bed_label);
        $bed_created = mysqli_stmt_execute($insert_bed);
        if (!$bed_created) {
            error_log("PG bed creation failed: " . mysqli_stmt_error($insert_bed));
        } else {
            $bed_id = mysqli_insert_id($con);
            $bed_status = 'vacant';
            $bed_student_id = null;
        }
        mysqli_stmt_close($insert_bed);
        if (!$bed_created) {
            $error = "Bed details could not be saved. Check whether another warden recorded this bed.";
            return false;
        }
    }

    if ($bed_status === 'maintenance') {
        $error = "That bed is marked for maintenance. Choose another bed.";
        return false;
    }
    if ($bed_status !== 'vacant' && (int)$bed_student_id !== $student_id) {
        $error = "That bed is already occupied. Enter another room or bed.";
        return false;
    }

    $student_stmt = mysqli_prepare($con, "SELECT bed_id FROM pg_students WHERE id = ? FOR UPDATE");
    if (!$student_stmt) {
        error_log("PG resident lookup prepare failed: " . mysqli_error($con));
        $error = "Resident assignment could not be checked. Please try again.";
        return false;
    }
    mysqli_stmt_bind_param($student_stmt, "i", $student_id);
    if (!mysqli_stmt_execute($student_stmt)) {
        error_log("PG resident lookup failed: " . mysqli_stmt_error($student_stmt));
        mysqli_stmt_close($student_stmt);
        $error = "Resident assignment could not be checked. Please try again.";
        return false;
    }
    mysqli_stmt_bind_result($student_stmt, $previous_bed_id);
    $student_found = mysqli_stmt_fetch($student_stmt);
    mysqli_stmt_close($student_stmt);
    if (!$student_found) {
        $error = "Resident account was not found.";
        return false;
    }

    $occupy_bed = mysqli_prepare($con, "UPDATE pg_beds SET student_id = ?, status = 'occupied' WHERE id = ? AND (status = 'vacant' OR student_id = ?)");
    if (!$occupy_bed) {
        error_log("PG bed assignment prepare failed: " . mysqli_error($con));
        $error = "Bed assignment could not be saved. Please try again.";
        return false;
    }
    mysqli_stmt_bind_param($occupy_bed, "iii", $student_id, $bed_id, $student_id);
    $bed_saved = mysqli_stmt_execute($occupy_bed);
    if (!$bed_saved) {
        error_log("PG bed assignment failed: " . mysqli_stmt_error($occupy_bed));
    }
    mysqli_stmt_close($occupy_bed);
    if (!$bed_saved) {
        $error = "Bed assignment could not be saved. Please try again.";
        return false;
    }

    $update_student = mysqli_prepare($con, "UPDATE pg_students SET room_id = ?, bed_id = ?, rent_amount = ?, deposit_amount = ? WHERE id = ?");
    if (!$update_student) {
        error_log("PG resident assignment prepare failed: " . mysqli_error($con));
        $error = "Resident stay details could not be saved. Please try again.";
        return false;
    }
    $rent_amount = (float)$rent_amount;
    $deposit_amount = (float)$deposit_amount;
    mysqli_stmt_bind_param($update_student, "iiddi", $room_id, $bed_id, $rent_amount, $deposit_amount, $student_id);
    $student_saved = mysqli_stmt_execute($update_student);
    if (!$student_saved) {
        error_log("PG resident assignment failed: " . mysqli_stmt_error($update_student));
    }
    mysqli_stmt_close($update_student);
    if (!$student_saved) {
        $error = "Resident stay details could not be saved. Please try again.";
        return false;
    }

    if ($previous_bed_id !== null && (int)$previous_bed_id !== (int)$bed_id) {
        $release_bed = mysqli_prepare($con, "UPDATE pg_beds SET student_id = NULL, status = 'vacant' WHERE id = ? AND student_id = ?");
        if (!$release_bed) {
            error_log("PG previous bed release prepare failed: " . mysqli_error($con));
            $error = "Previous bed assignment could not be updated. Please try again.";
            return false;
        }
        mysqli_stmt_bind_param($release_bed, "ii", $previous_bed_id, $student_id);
        $released = mysqli_stmt_execute($release_bed);
        if (!$released) {
            error_log("PG previous bed release failed: " . mysqli_stmt_error($release_bed));
        }
        mysqli_stmt_close($release_bed);
        if (!$released) {
            $error = "Previous bed assignment could not be updated. Please try again.";
            return false;
        }
    }

    return true;
}
