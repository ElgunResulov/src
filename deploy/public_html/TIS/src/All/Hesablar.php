<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

include('navbar_sidebar.php');

$roleConfigs = [
    'super_admin' => [
        'name' => 'SUPER ADMIN',
        'icon' => 'fas fa-crown',
        'users' => [],
    ],
    'admin' => [
        'name' => 'ADMIN',
        'icon' => 'fas fa-user-shield',
        'users' => [],
    ],
    'teacher' => [
        'name' => 'Müəllim',
        'icon' => 'fas fa-chalkboard-teacher',
        'users' => [],
    ],
    'student' => [
        'name' => 'Tələbə',
        'icon' => 'fas fa-user-graduate',
        'users' => [],
    ],
    'parent' => [
        'name' => 'Valideyn',
        'icon' => 'fas fa-heart',
        'users' => [],
    ],
    'staff' => [
        'name' => 'Əməkdaş',
        'icon' => 'fas fa-users',
        'users' => [],
    ],
    'examiner' => [
        'name' => 'İmtahan nəzarətçisi',
        'icon' => 'fas fa-clipboard-check',
        'users' => [],
    ],
    'operator' => [
        'name' => 'Operator',
        'icon' => 'fas fa-headset',
        'users' => [],
    ],
];

// Fetch users and organize by role
include('db.php');

if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    $company_id = isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 0;
    
    if ($role == 'super_admin' || $role == 'admin') {
        $sql = $role == 'super_admin' 
            ? "SELECT id, username, password, role, company_id FROM users ORDER BY created_at DESC"
            : "SELECT id, username, password, role FROM users WHERE company_id = ? ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($sql);
        
        if ($role == 'admin') {
            $stmt->bind_param("i", $company_id);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $userRole = $row['role'];
                if (isset($roleConfigs[$userRole])) {
                    $roleConfigs[$userRole]['users'][] = $row;
                }
            }
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title>TIS Əsas</title>
    <link href="../assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="../assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="../assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="../dist/css/style.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        /* Material Design Variables */
        :root {
            --primary-color: #1d6a9d;
            --primary-light: #2479b1;
            --primary-dark: #0d5a8d;
            --accent-color: #ff4081;
            --text-primary: #212121;
            --text-secondary: #757575;
            --divider-color: #BDBDBD;
            --background: #f5f5f5;
            --surface: #ffffff;
            --error: #B00020;
            --success: #4CAF50;
            --warning: #FF9800;
            --card-bg: #24425a;
        }

        /* Main Content */
        .main-content {
            margin-left: 0;
            padding: 20px;
            flex: 1;
            margin-top: 70px;
            display: flex;
            flex-wrap: wrap;
            gap: 0px;
            justify-content: flex-start;
            align-content: flex-start;
            transition: margin-left 0.3s ease;
        }

        /* General Card Styling */
        .main-content .card {
            height: 230px;
            width: calc(25% - 22px);
            min-width: 200px;
            padding: 12px;
            border-radius: 18px;
            background: #ffffff;
            color: #2b2b2b;
            transition: all 0.4s ease-in-out;
            cursor: pointer;
            margin-bottom: -5px;
            position: relative;
            margin-left: 20px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            animation: cardAppear 0.5s ease forwards;
        }

        @keyframes cardAppear {
            0% {
                opacity: 0;
                transform: scale(0.86);
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Hover Effects */
        .main-content .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 14px rgba(0, 0, 0, 0.15);
        }

        .main-content .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(120deg, rgba(255, 255, 255, 0.25) 0%, transparent 80%);
            transition: transform 0.6s ease;
        }

        .main-content .card:hover::before {
            transform: scale(1.2);
        }

        /* Card Text */
        .main-content .card p {
            margin: 10px 16px;
            font-family: 'Inter', sans-serif;
            font-size: 14.5px;
            line-height: 1.8;
            text-align: left;
            position: relative;
            z-index: 1;
            color: #333;
        }

        /* Divider Line */
        .main-content .card .line {
            height: 2px;
            width: 40px;
            background: rgba(0, 0, 0, 0.5);
            margin: 15px auto;
            transition: width 0.4s ease;
            border-radius: 4px;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.3);
        }

        /* Button Styling */
        .main-content .card button {
            display: block;
            margin: -10px auto 36px;
            padding: 10px 45px;
            background: rgba(255, 255, 255, 0.68);
            color: rgba(140, 140, 140, 0.8);
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 3px 8px rgba(213, 213, 213, 0.88);
        }

        .main-content .card button:hover {
            background: rgba(102, 41, 255, 0.73);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.25);
        }

        /* Delete Icon */
        .delete-icon {
            color: rgb(255, 128, 128);
            cursor: pointer;
            transition: color 0.35s;
            position: absolute;
            bottom: 10%;
            right: 88%;
            font-size: 18px;
        }

        .delete-icon:hover {
            color: rgb(255, 0, 8);
        }

        /* Company ID */
        .company-id {
            float: right;
            background-color: rgba(255, 255, 255, 0.53);
            border-radius: 6px;
            padding: 0px 5px;
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.2);
            font-weight: bold;
        }

        /* Add Button */
        .add-button {
            position: fixed;
            width: 45px;
            height: 45px;
            border-radius: 35px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            overflow: hidden;
            font-weight: bold;
            white-space: nowrap;
            z-index: 23;
            box-shadow: 0 4px 10px rgba(158, 158, 158, 0.64);
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, rgb(255, 255, 255), rgb(255, 255, 255));
            color: black;
        }

        .add-button:hover {
            width: 110px;
            background: linear-gradient(135deg, rgb(255, 255, 255), rgb(255, 255, 255));
            box-shadow: 0 4px 10px rgba(158, 158, 158, 0.64);
        }

        .add-button:active {
            transform: scale(0.95);
        }

        .translation-text {
            position: absolute;
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.3s ease-in-out;
            pointer-events: none;
            white-space: nowrap;
            padding: 0 15px;
        }

        .add-button:hover .translation-text {
            opacity: 1;
            transform: translateX(0);
        }

        .add-button:hover .original-content {
            opacity: 0;
            transform: translateX(-20px);
        }

        .original-content {
            transition: all 0.3s ease-in-out;
        }

        /* Form Overlay */
        .form-box {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(3px);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .form-box.show {
            display: flex;
            opacity: 1;
            align-items: center;
            justify-content: center;
        }

        /* Form Container */
        .form-container {
            background-color: var(--surface);
            width: 95%;
            max-width: 776px;
            border-radius: 12px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
            padding: 28px;
            height: auto;
            max-height: 100%;
            overflow-y: auto;
            box-sizing: border-box;
            animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            position: relative;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        /* Form Title */
        .form-container h2 {
            color: var(--primary-color);
            font-size: 22px;
            font-family: arial;
            margin: 0 0 28px 0;
            font-weight: 600;
            text-align: center;
            position: relative;
        }

        /* Form Fields */
        .form-field {
            position: relative;
            margin-bottom: 28px;
        }

        .form-field input {
            width: 100%;
            padding: 14px 0 10px 0;
            font-size: 16px;
            border: none;
            border-bottom: 1px solid var(--divider-color);
            background: transparent;
            transition: all 0.3s;
            outline: none;
        }

        .form-field label {
            position: absolute;
            top: 14px;
            left: 0;
            font-size: 16px;
            color: var(--text-secondary);
            pointer-events: none;
            transition: 0.3s ease all;
        }

        .form-field input:focus ~ label,
        .form-field input:valid ~ label {
            top: -12px;
            font-size: 12px;
            color: var(--primary-color);
            font-weight: 500;
        }

        .form-field .underline {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 2px;
            width: 100%;
        }

        .form-field .underline:before {
            content: "";
            position: absolute;
            height: 100%;
            width: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            transform: scaleX(0);
            transition: transform 0.3s ease;
            transform-origin: center;
        }

        .form-field input:focus ~ .underline:before {
            transform: scaleX(1);
        }

        /* Role Group */
        .role-group {
            background-color: var(--background);
            border-radius: 12px;
            padding: 18px;
            margin-bottom: 28px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.3s ease;
        }

        .role-group:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .role-group p {
            color: var(--primary-color);
            font-size: 15px;
            margin: 0 0 14px 0;
            font-weight: 500;
        }

        .role-options {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 14px;
        }

        .role-option {
            display: flex;
            align-items: center;
        }

        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }

        .role-option label {
            position: relative;
            padding-left: 30px;
            cursor: pointer;
            font-size: 14px;
            color: var(--text-primary);
            display: inline-block;
            transition: color 0.3s ease;
        }

        .role-option label:hover {
            color: var(--primary-color);
        }

        .role-option label:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 18px;
            height: 18px;
            border: 2px solid var(--text-secondary);
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .role-option input[type="radio"]:checked + label:before {
            border-color: var(--primary-color);
            border-width: 2px;
        }

        .role-option input[type="radio"]:checked + label:after {
            content: '';
            position: absolute;
            left: 0px;
            top: 0px;
            width: 18.5px;
            height: 18.5px;
            background: var(--primary-color);
            border-radius: 30px;
            animation: pulseRadio 0.3s ease;
        }

        @keyframes pulseRadio {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }

        /* Buttons */
        .button-group {
            display: flex;
            flex-direction: column;
            gap: 14px;
            margin-top: 28px;
        }

        .btn-confirm, .btn-cancel {
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            text-align: center;
            display: block;
        }

        .btn-confirm {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            box-shadow: 0 4px 10px rgba(29, 106, 157, 0.3);
        }

        .btn-confirm:hover {
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            box-shadow: 0 6px 15px rgba(29, 106, 157, 0.4);
            transform: translateY(-2px);
            color: white;
        }

        .btn-confirm:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(29, 106, 157, 0.3);
        }

        .btn-cancel {
            background-color: transparent;
            color: var(--primary-color);
            border: 1px solid rgba(29, 106, 157, 0.3);
        }

        .btn-cancel:hover {
            background-color: rgba(29, 106, 157, 0.05);
            border-color: var(--primary-color);
            transform: translateY(-2px);
            color: var(--primary-color);
            text-decoration: none;
        }

        .btn-cancel:active {
            transform: translateY(0);
        }

        /* Parent Modal Container */
        .parent-modal-container {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 11000;
            overflow: auto;
            backdrop-filter: blur(5px);
        }

        .parent-modal-container.show {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* Parent Modal Content */
        .parent-modal-content {
            background-color: var(--surface);
            border-radius: 16px;
            width: 90%;
            max-width: 700px;
            padding: 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: slideUp 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
            max-height: 68vh;
            overflow-y: auto;
        }

        .parent-modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 16px 16px 0 0;
        }

        /* Modal Close Button */
        .parent-modal-content .modal-close {
            position: relative;
            top: 10px;
            right: 0%;
            transform: translate(1700%,-0%);
            font-size: 28px;
            cursor: pointer;
            color: var(--text-secondary);
            transition: all 0.3s ease;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .parent-modal-content .modal-close:hover {
            color: var(--error);
            background-color: rgba(176, 0, 32, 0.1);
        }

        /* Modal Heading */
        .parent-modal-content h2 {
            margin: 0 0 32px;
            font-size: 24px;
            color: var(--primary-color);
            text-align: center;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
        }

        /* Search Container */
        .search-container {
            position: relative;
            margin-bottom: 28px;
        }

        .search-input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            font-size: 16px;
            border: 2px solid var(--divider-color);
            border-radius: 12px;
            background: var(--background);
            transition: all 0.3s ease;
            outline: none;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(29, 106, 157, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 18px;
        }

        /* Clear Search Button */
        .clear-search {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .clear-search:hover {
            color: red;
        }

        /* Loading Spinner */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Students Container */
        .students-container {
            margin-top: 24px;
            max-height: auto;
            overflow-y: auto;
        }

        /* Student Card */
        .student-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 3px solid transparent;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .student-card:hover {
            border-top:3px solid rgb(53, 113, 187);
            border-right:3px solid rgb(53, 113, 187);
            border-left:3px solid rgb(53, 113, 187);
            border-bottom:3px solid rgb(53, 113, 187);
        }

        .student-card:hover::before {
            transform: scaleX(1);
        }

        .student-info {
            margin-bottom: 16px;
        }

        .student-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .parent-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 16px;
        }

        .parent-detail {
            background: white;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid var(--divider-color);
            transition: all 0.3s ease;
        }

        .parent-detail.available {
            border-left-color: var(--success);
        }

        .parent-detail.unavailable {
            border-left-color: var(--error);
            opacity: 0.6;
        }

        .parent-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .parent-name {
            font-size: 14px;
            color: var(--text-primary);
            font-weight: 500;
        }

        /* Parent Selection Buttons */
        .parent-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
        }

        .select-parent-btn {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid var(--primary-color);
            background: transparent;
            color: var(--primary-color);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .select-parent-btn:hover:not(:disabled) {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(29, 106, 157, 0.3);
        }

        .select-parent-btn:disabled {
            border-color: var(--divider-color);
            color: var(--text-secondary);
            cursor: not-allowed;
            opacity: 0.5;
        }

        .select-parent-btn.selected {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(29, 106, 157, 0.3);
        }

        .select-parent-btn.selected::after {
            content: '✓';
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            font-weight: bold;
        }

        /* Submit Button */
        .submit-parent-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 24px;
            opacity: 0.5;
            pointer-events: none;
        }

        .submit-parent-btn.enabled {
            opacity: 1;
            pointer-events: all;
        }

        .submit-parent-btn.enabled:hover {
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(29, 106, 157, 0.4);
        }

        /* No Results Message */
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-secondary);
        }

        .no-results i {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .no-results h3 {
            margin: 0 0 8px 0;
            color: var(--text-primary);
        }

        .no-results p {
            margin: 0;
            font-size: 14px;
        }

        /* Selected Parent Info */
        .selected-parent-info {
            display: none;
            background: linear-gradient(135deg, rgba(29, 106, 157, 0.05) 0%, rgba(36, 121, 177, 0.05) 100%);
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            animation: slideDown 0.3s ease;
        }

        .selected-parent-info.show {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .selected-info-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .selected-info-details {
            font-size: 14px;
            color: var(--text-primary);
        }

        /* Password Info Alert */
        .password-info {
            background: linear-gradient(135deg, rgba(29, 106, 157, 0.05) 0%, rgba(36, 121, 177, 0.05) 100%);
            border: 2px solid var(--primary-color);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
            text-align: center;
        }

        .password-info i {
            color: var(--primary-color);
            font-size: 24px;
            margin-bottom: 8px;
        }

        .password-info p {
            margin: 0;
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-content .card {
                width: calc(33.33% - 22px);
            }
        }

        @media (max-width: 992px) {
            .main-content .card {
                width: calc(50% - 22px);
            }
        }

        @media (max-width: 768px) {
            /* Modal Close Button */
            .parent-modal-content .modal-close {
                transform: translate(0%,0%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .main-content .card {
                width: 100%;
                margin-left: 0px;
            }
            
            .add-button {
                width: 45px;
            }
            
            .add-button:hover {
                width: 45px;
            }
            
            .parent-info {
                grid-template-columns: 1fr;
            }
            
            .parent-buttons {
                flex-direction: column;
            }
        }

        @media (min-width: 769px) {
            .main-content {
                margin-left: 250px;
            }
        }

        @media (max-width: 600px) {
            .parent-modal-content {
                width: 95%;
                padding: 20px;
            }
            
            .parent-modal-content h2 {
                font-size: 20px;
            }
        }

        /* Loading Animation */
        .lds-ripple {
            display: inline-block;
            position: relative;
            width: 80px;
            height: 80px;
        }

        .lds-ripple div {
            position: absolute;
            border: 4px solid #3182ce;
            opacity: 1;
            border-radius: 50%;
            animation: lds-ripple 1s cubic-bezier(0, 0.2, 0.8, 1) infinite;
        }

        .lds-ripple div:nth-child(2) {
            animation-delay: -0.5s;
        }

        @keyframes lds-ripple {
            0% {
                top: 36px;
                left: 36px;
                width: 0;
                height: 0;
                opacity: 1;
            }
            100% {
                top: 0;
                left: 0;
                width: 72px;
                height: 72px;
                opacity: 0;
            }
        }

        .tarix_folder {
            width: 95%;
            max-width: 99%;
            margin: 0 auto 12px;
            background: linear-gradient(145deg, #ffffff90, #f1f1f177);
            border-radius: 16px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08), 0 2px 4px rgba(0, 0, 0, 0.06);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            min-height: 52px;
            overflow: hidden;
            border: 1px solid transparent;
        }

        .tarix_folder:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1), 0 3px 6px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .tarix_folder[data-state="up"] {
            background: linear-gradient(145deg, #f8fafc, #e2e8f0);
            border: 1px solid #cbd5e1;
        }

        .tarix_folder .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            height: 52px;
            padding: 0 15px 0 40px;
            position: relative;
            cursor: pointer;
        }

        .tarix_folder .header .folder-icon {
            position: absolute;
            left: 15px;
            font-size: 1.1rem;
            color: #4b5563;
            transition: transform 0.3s ease;
        }

        .tarix_folder:hover .header .folder-icon {
            transform: scale(1.1);
        }

        .tarix_folder p {
            color: #4b5563;
            font-size: 1rem;
            font-weight: 500;
            margin: 0;
            line-height: 1.5;
            flex-grow: 1;
            text-align: center;
        }

        .tarix_folder .header p {
          font-family: Arial;
          font-weight: bold;
        }

        .tarix_folder .header::after {
            content: '\f078';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: 15px;
            font-size: 1rem;
            color: #4b5563;
            transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .tarix_folder[data-state="up"] .header::after {
            transform: rotate(180deg);
        }

        .tarix_folder .content {
            max-height: 0;
            opacity: 0;
            transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.4s ease, padding 0.4s ease;
            width: 100%;
            padding: 0 15px;
            box-sizing: border-box;
            text-align: left;
        }

        .tarix_folder[data-state="up"] .content {
            max-height: 2000px;
            opacity: 1;
            padding: 15px;
        }

        .folder-cards-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 10px;
            justify-content: flex-start;
            align-content: flex-start;
        }

        .folder-cards-container .card {
            height: 230px;
            width: calc(25% - 15px);
            min-width: 200px;
            padding: 12px;
            border-radius: 18px;
            background: #ffffff;
            color: #2b2b2b;
            transition: all 0.4s ease-in-out;
            cursor: pointer;
            position: relative;
            margin-left: 0;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            animation: cardAppear 0.5s ease forwards;
        }

        @media (max-width: 1200px) {
            .folder-cards-container .card {
                width: calc(33.33% - 15px);
            }
        }

        @media (max-width: 992px) {
            .folder-cards-container .card {
                width: calc(50% - 15px);
            }
        }

        @media (max-width: 768px) {
            .tarix_folder {
                min-height: 46px;
                border-radius: 12px;
                margin-bottom: 10px;
            }

            .tarix_folder .header {
                height: 46px;
                padding: 0 10px 0 35px;
            }

            .tarix_folder p {
                font-size: 13px;
            }

            .tarix_folder .header .folder-icon {
                left: 10px;
                font-size: 1rem;
            }

            .tarix_folder .header::after {
                right: 10px;
                font-size: 0.9rem;
            }

            .tarix_folder[data-state="up"] .content {
                max-height: 2000px;
            }

            .folder-cards-container .card {
                width: 100%;
                margin-left: 0px;
            }
        }
    </style>
</head>

<body>
    <div class="preloader">
        <div class="lds-ripple">
            <div></div>
            <div></div>
        </div>
    </div>
    
    <button class="add-button" onclick="showAddUserForm()">
        <span style="font-size:28px;" class="original-content">+</span>
        <span style="font-size:16px;" class="translation-text">Əlavə et</span>
    </button>
    
    <div class="main-content main">
        <?php foreach ($roleConfigs as $role => $config): ?>
            <div class="tarix_folder" data-state="down" data-role="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
                <div class="header">
                    <i class="<?php echo htmlspecialchars($config['icon']); ?> folder-icon"></i>
                    <p><?php echo htmlspecialchars($config['name']) . ' - (' . count($config['users']) . ')'; ?></p>
                </div>
                <div class="content">
                    <?php if (!empty($config['users'])): ?>
                        <!-- Search Input -->
                        <div class="mb-3 mt-2">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input 
                                    type="text" 
                                    class="form-control user-search-input" 
                                    placeholder="İstifadəçi adı ilə axtarın..." 
                                    data-role="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>"
                                    onkeyup="searchUsers(this)"
                                >
                                <button class="btn btn-outline-secondary clear-search-btn" type="button" onclick="clearUserSearch(this)" style="display: none;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="folder-cards-container" id="cards-container-<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if (empty($config['users'])): ?>
                            <div style="width: 100%; text-align: center; padding: 40px; color: #757575;">
                                <i class="fas fa-users" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                                <h3 style="margin: 0 0 8px 0; color: #212121;">Heç bir istifadəçi tapılmadı</h3>
                                <p style="margin: 0; font-size: 14px;">Bu rol üçün hələ istifadəçi əlavə edilməyib</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($config['users'] as $user): ?>
                                <div class='card user-card' data-username="<?php echo htmlspecialchars(strtolower($user['username']), ENT_QUOTES, 'UTF-8'); ?>">
                                    <p>Adı: <?php echo htmlspecialchars($user['username']); ?>
                                        <?php if ($_SESSION['role'] === 'super_admin' && isset($user['company_id'])): ?>
                                            <span hidden class='company-id'><?php echo htmlspecialchars($user['company_id']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <p>Parol: 
                                        <span id='password-text-<?php echo (int) $user['id']; ?>'>****</span>
                                        <span id='real-password-<?php echo (int) $user['id']; ?>' style='display:none;'><?php echo htmlspecialchars($user['password'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        <i id='toggle-password-<?php echo (int) $user['id']; ?>' class='fa fa-eye' onclick='togglePasswordVisibility(<?php echo (int) $user['id']; ?>)'></i>
                                    </p>
                                    <p>Səlahiyyət: <?php echo htmlspecialchars($user['role']); ?></p>
                                    <div class='line'></div>
                                    <a style='text-decoration: none;' href='edit_user.php?id=<?php echo (int) $user['id']; ?>'>
                                        <button>Redaktə</button>
                                    </a>
                                    <?php if ($_SESSION['role'] == 'super_admin' || $_SESSION['role'] == 'admin'): ?>
                                        <i class='fas fa-trash-alt delete-icon' data-id='<?php echo (int) $user['id']; ?>'></i>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- No Results Message -->
                    <div class="no-search-results" id="no-results-<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>" style="display: none;">
                        <div style="width: 100%; text-align: center; padding: 40px; color: #757575;">
                            <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px; opacity: 0.5;"></i>
                            <h3 style="margin: 0 0 8px 0; color: #212121;">Heç bir nəticə tapılmadı</h3>
                            <p style="margin: 0; font-size: 14px;">Axtarış kriteriyalarınızı dəyişdirin</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Add User Form -->
    <div id="addUserForm" class="form-box">
        <div class="form-container">
            <h2>Yeni İstifadəçi Yarat</h2>
            <form id="userForm" method="POST" action="process_add_user.php">
                <!-- Parent Selection Modal -->
                <div id="parentModal" class="parent-modal-container">
                    <div class="parent-modal-content">
                        <span class="modal-close" onclick="closeParentModal()">×</span>
                        <h2>Valideyn Seçimi</h2>
                        
                        <div id="selectedParentInfo" class="selected-parent-info">
                            <div class="selected-info-title">Seçilmiş Valideyn:</div>
                            <div id="selectedInfoDetails" class="selected-info-details"></div>
                        </div>
                        
                        <div class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="studentSearch" class="search-input" placeholder="Tələbə adını axtarın..." oninput="handleSearchInput()">
                            <button type="button" class="clear-search" onclick="clearSearch()" style="display: none;">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <div class="loading-spinner" id="loadingSpinner">
                            <div class="spinner"></div>
                            <p>Axtarılır...</p>
                        </div>
                        
                        <div class="students-container" id="studentsContainer">
                            <div class="no-results">
                                <i class="fas fa-search"></i>
                                <h3>Axtarış başladın</h3>
                                <p>Tələbə adını daxil edin</p>
                            </div>
                        </div>
                        
                        <button type="button" class="submit-parent-btn" id="submitParentBtn" onclick="submitParentSelection()">
                            Təsdiq
                        </button>
                    </div>
                </div>

                <div class="form-field">
                    <input type="text" id="username" name="username" required>
                    <label for="username">İstifadəçi adı</label>
                    <div class="underline"></div>
                </div>

                <div class="password-info">
                    <i class="fas fa-key"></i>
                    <p><strong>Qeyd:</strong> Şifrə avtomatik yaradılacaq və uğurlu əməliyyatdan sonra göstəriləcək.</p>
                </div>

                <div class="role-group">
                    <p>Səlahiyyət:</p>
                    <div class="role-options">
                        <?php if ($_SESSION['role'] === 'super_admin'): ?>
                        <div class="role-option">
                            <input type="radio" id="role_super_admin" name="role" value="super_admin">
                            <label for="role_super_admin">Super Admin</label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="role_admin" name="role" value="admin">
                            <label for="role_admin">Admin</label>
                        </div>
                        <?php endif; ?>
                        <div hidden class="role-option">
                            <input type="radio" id="role_teacher" name="role" value="teacher">
                            <label for="role_teacher">Müəllim</label>
                        </div>
                        <div hidden class="role-option">
                            <input type="radio" id="role_student" name="role" value="student">
                            <label for="role_student">Tələbə</label>
                        </div>
                        <div hidden class="role-option">
                            <input type="radio" id="role_staff" name="role" value="staff">
                            <label for="role_staff">Əməkdaş</label>
                        </div>
                      <div hidden>
                        <div class="role-option">
                            <input type="radio" id="role_parent" name="role" value="parent">
                            <label for="role_parent">Valideyn</label>
                        </div>
                      </div>
                        <div class="role-option">
                            <input type="radio" id="role_examiner" name="role" value="examiner">
                            <label for="role_examiner">İmtahan nəzarətçisi</label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="role_operator" name="role" value="operator">
                            <label for="role_operator">Operator</label>
                        </div>
                    </div>
                </div>

                <input type="hidden" id="selected_student" name="selected_student">
                <input type="hidden" id="selected_parent" name="selected_parent">
                <input type="hidden" id="parent_type" name="parent_type">

                <div class="button-group">
                    <button type="submit" class="btn-confirm">Təsdiq</button>
                    <button type="button" class="btn-cancel" onclick="hideAddUserForm()">Geri</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let selectedStudent = null;
        let selectedParent = null;
        let selectedParentType = null;
        let searchTimeout = null;

        // User search functionality
        function searchUsers(input) {
            const searchTerm = input.value.toLowerCase().trim();
            const role = input.getAttribute('data-role');
            const cardsContainer = document.getElementById('cards-container-' + role);
            const noResultsDiv = document.getElementById('no-results-' + role);
            const clearBtn = input.parentElement.querySelector('.clear-search-btn');
            
            // Show/hide clear button
            if (searchTerm) {
                clearBtn.style.display = 'block';
            } else {
                clearBtn.style.display = 'none';
            }
            
            const userCards = cardsContainer.querySelectorAll('.user-card');
            let visibleCount = 0;
            
            userCards.forEach(card => {
                const username = card.getAttribute('data-username');
                if (username.includes(searchTerm)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            if (visibleCount === 0 && searchTerm) {
                noResultsDiv.style.display = 'block';
            } else {
                noResultsDiv.style.display = 'none';
            }
        }

        function clearUserSearch(button) {
            const inputGroup = button.parentElement;
            const input = inputGroup.querySelector('.user-search-input');
            const role = input.getAttribute('data-role');
            const cardsContainer = document.getElementById('cards-container-' + role);
            const noResultsDiv = document.getElementById('no-results-' + role);
            
            input.value = '';
            button.style.display = 'none';
            
            // Show all cards
            const userCards = cardsContainer.querySelectorAll('.user-card');
            userCards.forEach(card => {
                card.style.display = 'block';
            });
            
            // Hide no results message
            noResultsDiv.style.display = 'none';
        }

        function showAddUserForm() {
            const addUserForm = document.getElementById('addUserForm');
            const addButton = document.querySelector('.add-button');
            const mainContent = document.querySelector('.main');
            
            if (addUserForm && addButton && mainContent) {
                addUserForm.classList.add('show');
                addButton.style.opacity = "0";
                mainContent.style.display = "none";
            }
        }

        function hideAddUserForm() {
            const addUserForm = document.getElementById('addUserForm');
            const addButton = document.querySelector('.add-button');
            const mainContent = document.querySelector('.main');
            const userForm = document.getElementById('userForm');
            
            if (addUserForm && addButton && mainContent) {
                addUserForm.classList.remove('show');
                addButton.style.opacity = "1";
                mainContent.style.display = "block";
                
                if (userForm) {
                    userForm.reset();
                }
                closeParentModal();
                location.reload();
            }
        }

        function togglePasswordVisibility(id) {
            const passwordText = document.getElementById(`password-text-${id}`);
            const realPassword = document.getElementById(`real-password-${id}`);
            const toggleIcon = document.getElementById(`toggle-password-${id}`);
            
            if (passwordText && realPassword && toggleIcon) {
                if (passwordText.style.display === 'none') {
                    passwordText.style.display = 'inline';
                    realPassword.style.display = 'none';
                    toggleIcon.className = 'fa fa-eye';
                } else {
                    passwordText.style.display = 'none';
                    realPassword.style.display = 'inline';
                    toggleIcon.className = 'fa fa-eye-slash';
                }
            }
        }

        function closeParentModal() {
            const parentModal = document.getElementById('parentModal');
            const studentSearch = document.getElementById('studentSearch');
            const clearSearchBtn = document.querySelector('.clear-search');
            
            if (parentModal) {
                parentModal.classList.remove('show');
            }
            
            if (studentSearch) {
                studentSearch.value = '';
            }
            
            if (clearSearchBtn) {
                clearSearchBtn.style.display = 'none';
            }
            
            resetSearchResults();
            resetParentSelection();
        }

        function submitParentSelection() {
            if (selectedStudent && selectedParent && selectedParentType) {
                const parentModal = document.getElementById('parentModal');
                const usernameField = document.getElementById('username');
                
                if (parentModal) {
                    parentModal.classList.remove('show');
                }
                
                if (usernameField && selectedParent) {
                    const cleanUsername = selectedParent.toLowerCase()
                        .replace(/[^a-zA-Z0-9]/g, '')
                        .substring(0, 15);
                    usernameField.value = cleanUsername;
                }
            } else {
                alert('Zəhmət olmasa bir valideyn seçin.');
            }
        }

        function clearSearch() {
            const studentSearch = document.getElementById('studentSearch');
            const clearSearchBtn = document.querySelector('.clear-search');
            
            if (studentSearch) {
                studentSearch.value = '';
            }
            
            if (clearSearchBtn) {
                clearSearchBtn.style.display = 'none';
            }
            
            fetchAllStudents();
        }

        function handleSearchInput() {
            const studentSearch = document.getElementById('studentSearch');
            const clearSearchBtn = document.querySelector('.clear-search');
            
            if (!studentSearch) return;
            
            const query = studentSearch.value.trim();
            
            if (clearSearchBtn) {
                clearSearchBtn.style.display = query ? 'block' : 'none';
            }
            
            if (searchTimeout) {
                clearTimeout(searchTimeout);
            }
            
            searchTimeout = setTimeout(() => {
                if (query) {
                    searchStudent(query);
                } else {
                    fetchAllStudents();
                }
            }, 300);
        }

        function fetchAllStudents() {
            const studentsContainer = document.getElementById('studentsContainer');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            if (!studentsContainer || !loadingSpinner) return;
            
            loadingSpinner.style.display = 'block';
            studentsContainer.innerHTML = '';
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'valideyn/search_students.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                loadingSpinner.style.display = 'none';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        displaySearchResults(response.students || []);
                    } catch (e) {
                        showError('JSON parse error: ' + e.message);
                    }
                } else {
                    showError('Server error (Status: ' + xhr.status + ')');
                }
            };
            
            xhr.onerror = function() {
                loadingSpinner.style.display = 'none';
                showError('Connection error');
            };
            
            xhr.send();
        }

        function searchStudent(query) {
            const studentsContainer = document.getElementById('studentsContainer');
            const loadingSpinner = document.getElementById('loadingSpinner');
            
            if (!studentsContainer || !loadingSpinner) return;
            
            loadingSpinner.style.display = 'block';
            studentsContainer.innerHTML = '';
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'valideyn/search_students.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                loadingSpinner.style.display = 'none';
                
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        displaySearchResults(response.students || []);
                    } catch (e) {
                        showError('JSON parse error: ' + e.message);
                    }
                } else {
                    showError('Server error (Status: ' + xhr.status + ')');
                }
            };
            
            xhr.onerror = function() {
                loadingSpinner.style.display = 'none';
                showError('Connection error');
            };
            
            xhr.send('query=' + encodeURIComponent(query));
        }

        function displaySearchResults(students) {
            const studentsContainer = document.getElementById('studentsContainer');
            if (!studentsContainer) return;
            
            studentsContainer.innerHTML = '';
            
            if (students.length === 0) {
                studentsContainer.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-user-slash"></i>
                        <h3>Heç bir tələbə tapılmadı</h3>
                        <p>Axtarış kriteriyalarınızı dəyişdirin</p>
                    </div>
                `;
                return;
            }

            students.forEach(student => {
                const studentDiv = document.createElement('div');
                studentDiv.className = 'student-card';
                
                const hasAta = student.ata && student.ata.trim() !== '';
                const hasAna = student.ana && student.ana.trim() !== '';
                
                studentDiv.innerHTML = `
                    <div class="student-info">
                        <div class="student-name">
                            <i class="fas fa-user-graduate"></i>
                            ${escapeHtml(student.username || 'N/A')}
                        </div>
                    </div>
                    
                    <div class="parent-info">
                        <div class="parent-detail ${hasAta ? 'available' : 'unavailable'}">
                            <div class="parent-label">Ata</div>
                            <div class="parent-name">${escapeHtml(student.ata || 'Məlumat yoxdur')}</div>
                        </div>
                        <div class="parent-detail ${hasAna ? 'available' : 'unavailable'}">
                            <div class="parent-label">Ana</div>
                            <div class="parent-name">${escapeHtml(student.ana || 'Məlumat yoxdur')}</div>
                        </div>
                    </div>
                    
                    <div class="parent-buttons">
                        <button type="button" class="select-parent-btn"
                                data-student="${escapeHtml(student.username || '')}"
                                data-parent="${escapeHtml(student.ata || '')}"
                                data-type="ata"
                                ${!hasAta ? 'disabled' : ''}>
                            <i class="fas fa-male"></i> Ata seç
                        </button>
                        <button type="button" class="select-parent-btn"
                                data-student="${escapeHtml(student.username || '')}"
                                data-parent="${escapeHtml(student.ana || '')}"
                                data-type="ana"
                                ${!hasAna ? 'disabled' : ''}>
                            <i class="fas fa-female"></i> Ana seç
                        </button>
                    </div>
                `;
                
                studentsContainer.appendChild(studentDiv);
            });

            attachSelectionListeners();
        }

        function attachSelectionListeners() {
            document.querySelectorAll('.select-parent-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (this.disabled) return;
                    
                    document.querySelectorAll('.select-parent-btn').forEach(b => b.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    selectedStudent = this.getAttribute('data-student');
                    selectedParent = this.getAttribute('data-parent');
                    selectedParentType = this.getAttribute('data-type');
                    
                    const selectedStudentField = document.getElementById('selected_student');
                    const selectedParentField = document.getElementById('selected_parent');
                    const parentTypeField = document.getElementById('parent_type');
                    
                    if (selectedStudentField) selectedStudentField.value = selectedStudent || '';
                    if (selectedParentField) selectedParentField.value = selectedParent || '';
                    if (parentTypeField) parentTypeField.value = selectedParentType || '';
                    
                    showSelectedParentInfo();
                    
                    const submitParentBtn = document.getElementById('submitParentBtn');
                    if (submitParentBtn) {
                        submitParentBtn.classList.add('enabled');
                    }
                });
            });
        }

        function showSelectedParentInfo() {
            const selectedParentInfo = document.getElementById('selectedParentInfo');
            const selectedInfoDetails = document.getElementById('selectedInfoDetails');
            
            if (selectedStudent && selectedParent && selectedParentType && selectedParentInfo && selectedInfoDetails) {
                const parentTypeText = selectedParentType === 'ata' ? 'Ata' : 'Ana';
                selectedInfoDetails.innerHTML = `
                    <strong>Tələbə:</strong> ${escapeHtml(selectedStudent)}<br>
                    <strong>${parentTypeText}:</strong> ${escapeHtml(selectedParent)}
                `;
                selectedParentInfo.classList.add('show');
            }
        }

        function resetParentSelection() {
            selectedStudent = null;
            selectedParent = null;
            selectedParentType = null;
            
            const selectedStudentField = document.getElementById('selected_student');
            const selectedParentField = document.getElementById('selected_parent');
            const parentTypeField = document.getElementById('parent_type');
            const selectedParentInfo = document.getElementById('selectedParentInfo');
            const submitParentBtn = document.getElementById('submitParentBtn');
            
            if (selectedStudentField) selectedStudentField.value = '';
            if (selectedParentField) selectedParentField.value = '';
            if (parentTypeField) parentTypeField.value = '';
            if (selectedParentInfo) selectedParentInfo.classList.remove('show');
            if (submitParentBtn) submitParentBtn.classList.remove('enabled');
            
            document.querySelectorAll('.select-parent-btn').forEach(btn => btn.classList.remove('selected'));
        }

        function resetSearchResults() {
            const studentsContainer = document.getElementById('studentsContainer');
            if (studentsContainer) {
                studentsContainer.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>Bütün tələbələr yüklənir</h3>
                        <p>Tələbə adını daxil edin və ya bütün tələbələri görmək üçün gözləyin</p>
                    </div>
                `;
            }
            fetchAllStudents();
        }

        function showError(message) {
            const studentsContainer = document.getElementById('studentsContainer');
            if (studentsContainer) {
                studentsContainer.innerHTML = `
                    <div class="no-results">
                        <i class="fas fa-exclamation-triangle" style="color: var(--error);"></i>
                        <h3>Xəta baş verdi</h3>
                        <p>${escapeHtml(message)}</p>
                    </div>
                `;
            }
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text ? text.replace(/[&<>"']/g, function(m) { return map[m]; }) : '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const parentRadio = document.getElementById('role_parent');
            const parentModal = document.getElementById('parentModal');
            const studentSearch = document.getElementById('studentSearch');

            if (parentRadio && parentModal) {
                parentRadio.addEventListener('change', function() {
                    if (this.checked) {
                        parentModal.classList.add('show');
                        resetParentSelection();
                        resetSearchResults();
                    }
                });
            }

            const modalClose = document.querySelector('.modal-close');
            if (modalClose) {
                modalClose.addEventListener('click', closeParentModal);
            }

            if (parentModal) {
                parentModal.addEventListener('click', function(event) {
                    if (event.target === parentModal) {
                        closeParentModal();
                    }
                });
            }

            if (studentSearch) {
                studentSearch.addEventListener('input', handleSearchInput);
            }

            const clearSearchBtn = document.querySelector('.clear-search');
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', clearSearch);
            }

            const deleteIcons = document.querySelectorAll('.delete-icon');
            deleteIcons.forEach(icon => {
                icon.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    if (confirm('Bu istifadəçini silmək istədiyinizə əminsiniz?')) {
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', 'delete_user.php', true);
                        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                        xhr.onload = function() {
                            if (this.status === 200) {
                                location.reload();
                            } else {
                                alert('Xəta baş verdi. Zəhmət olmasa yenidən cəhd edin.');
                            }
                        };
                        xhr.send('id=' + userId);
                    }
                });
            });

            resetSearchResults();
        });

        document.querySelectorAll('.tarix_folder').forEach(folder => {
            folder.addEventListener('click', (e) => {
                // Don't toggle if clicking on search input or buttons
                if (e.target.closest('.input-group') || e.target.closest('.user-card')) {
                    return;
                }
                folder.dataset.state = folder.dataset.state === 'down' ? 'up' : 'down';
            });
        });
    </script>

    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="../assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>