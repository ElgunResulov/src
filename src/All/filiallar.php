<?php
include('db.php');
include('navbar_sidebar.php');
?>

<!DOCTYPE html>
<html lang="az">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Filial İdarəetmə Sistemi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .schedule-grid {
            display: grid;
            grid-template-columns: 80px repeat(7, 1fr);
            gap: 8px;
            background: #f8fafc;
            border-radius: 1rem;
            padding: 1.5rem;
            overflow-x: auto;
            overflow-y: auto;
            min-width: 800px;
            max-height: auto; 
            position: relative; 
            height: auto; 
        }

        .schedule-header {
            background: linear-gradient(135deg, rgb(98, 121, 223));
            color: white;
            padding: 0.75rem 0.5rem;
            text-align: center;
            font-weight: bold;
            border-radius: 0.5rem;
            font-size: 0.86rem;
            word-wrap: break-word;
            position: relative;
            top: 0rem;
            z-index: 10;
            background-clip: padding-box;
        }

        /* ===== SCHEDULE TIME ===== */
        .schedule-time {
            background: #e2e8f0;
            padding: 1rem 0.5rem;
            text-align: center;
            font-weight: 600;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            color: #2d3748;
            position: sticky;
            left: 0;
            z-index: 10;
        }

        /* ===== SCHEDULE CELL ===== */
        .schedule-cell {
            background: white;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            border: 2px solid transparent;
            font-weight: 500;
            text-align: center;
            padding: 0.25rem;
        }

        .schedule-cell:hover {
            background: rgba(102, 126, 234, 0.1);
            border-color: #667eea;
            transform: scale(1);
        }

        .schedule-cell.selected {
            background: linear-gradient(135deg, rgb(34, 176, 136));
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .schedule-cell.occupied {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }


        /* ===== SCHEDULE DISPLAY GRID ===== */
        .schedule-display-grid {
            display: grid;
            grid-template-columns: 80px 150px 1fr;
            gap: 8px;
            background: #f8fafc;
            border-radius: 0.75rem;
            padding: 1rem;
            margin: 1rem 0;
        }

        .schedule-display-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 0.75rem;
            text-align: center;
            font-weight: 600;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        .schedule-display-cell {
            background: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
            color: #2d3748;
            text-align: center;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            font-size: 16px;
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* ===== MAIN LAYOUT ===== */
        .main-wrapper {
            margin-top: 10px;
            margin-left: 250px;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        /* ===== HEADER ===== */
        .header {
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .header-content {
            padding: 1.5rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .header h1 {
            color: #2d3748;
            font-size: 1.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .header h1 i {
            color: #667eea;
            font-size: 1.5rem;
        }

        /* ===== NAVIGATION ===== */
        .nav-container {
            position: relative;
            top: 75px;
            z-index: 9;
        }

        .nav-tabs {
            display: flex;
            padding: 0.5rem 2rem;
            gap: 0.65rem;
            max-width: 1400px;
            margin: 0 auto;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .nav-tabs::-webkit-scrollbar {
            display: none;
        }

        .nav-tab {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.47rem 1.5rem;
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 1.2rem;
            font-weight: 500;
            font-size: 0.9rem;
            white-space: nowrap;
            position: relative;
        }

        .nav-tab:hover {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            transform: translateY(-1px);
        }

        .nav-tab.active {
            background: #667eea;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .nav-tab i {
            font-size: 1rem;
        }

        /* ===== MAIN CONTENT ===== */
        .content {
            padding: 1.3rem;
            max-width: 100%;
            margin: 0% auto;
        }

        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-header h2 {
            color: white;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .page-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
        }

        /* ===== TAB CONTENT ===== */
        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeInUp 0.4s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== CARDS ===== */
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-header {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h3 {
            color: #2d3748;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .card-body {
            padding: 2rem;
        }

        /* ===== FORMS ===== */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            gap: 10px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #374151;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: white;
            color: #374151;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 10px 25px;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .btn:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn:hover:before {
            left: 100%;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(239, 68, 68, 0.4);
        }

        .btn-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }

        .btn-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            color: white;
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 114, 128, 0.4);
        }

        /* ===== GRID LAYOUT ===== */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
        }

        .grid-item {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .grid-item:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .grid-item:hover:before {
            transform: scaleX(1);
        }

        .grid-item:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .grid-item h3 {
            color: #2d3748;
            margin-bottom: 1rem;
            font-size: 1.45rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .grid-item h4 i {
            color: #667eea;
            font-size: 1.4rem;
            margin-bottom: 4px;
            margin-right: 4px;
        }

        .grid-item h3 i {
            color: #667eea;
            font-size: 1.4rem;
        }

        .grid-item .info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .grid-item .info i {
            width: 16px;
            color: #667eea;
            font-size: 0.9rem;
        }

        /* ===== MODAL ===== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 1rem;
            min-width: 100%;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        /* ===== TEACHER MODAL SPECIFIC STYLES ===== */
        .modal-dialog {
            max-width: 92% !important;
            width: 92% !important;
        }

        .modal-dialog .modal-content {
            width: 92% !important;
            height: auto !important;
            max-height: auto !important;
            display: flex;
            flex-direction: column;
        }

        .modal-dialog .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem 2rem;
        }

        .modal-dialog .modal-header {
            padding: 1.25rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
            flex-shrink: 0;
        }

        .modal-dialog .modal-footer {
            padding: 1.25rem 2rem;
            border-top: 1px solid #e5e7eb;
            background: #f8fafc;
            flex-shrink: 0;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        @keyframes modalSlideIn {
            from {
                transform: scale(0.9) translateY(-20px);
                opacity: 0;
            }
            to {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .modal-header h3 {
            color: #2d3748;
            font-size: 1.25rem;
            font-weight: 600;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.7rem;
            cursor: pointer;
            color: #64748b;
            font-weight: bolder;
            padding: 0.25rem;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .modal-close:hover {
            color: #ef4444;
        }

        .modal-body {
            padding: 2rem;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e5e7eb;
            background: #f8fafc;
            text-align: right;
        }

        /* ===== FILIAL DETAILS MODAL ===== */
        .filial-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .filial-info-item {
            text-align: center;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
        }

        .filial-info-label {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .filial-info-value {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.1rem;
        }

        .teachers-section h3 {
            margin-bottom: 1.5rem;
            color: #2d3748;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .teacher-item {
            background: #f8fafc;
            padding: 1.5rem;
            margin: 1rem 0;
            border-radius: 0.75rem;
            border-left: 4px solid #667eea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .teacher-info h4 {
            color: #2d3748;
            text-align: left;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .teacher-details {
            color: #64748b;
            font-size: 0.9rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .teacher-details span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .teacher-details i {
            font-size: 0.8rem;
        }

        .teacher-actions .btn {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }

        .empty-teachers {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .empty-teachers i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e0;
        }

        /* ===== NOTIFICATION STYLES ===== */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem 1.5rem;
            border-radius: 0.75rem;
            color: white;
            font-weight: 500;
            z-index: 10001;
            max-width: 400px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideInRight 0.4s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .notification.success {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .notification.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
        }

        .notification.info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
        }

        .notification.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
        }

        .notification i {
            font-size: 1.2rem;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        .notification.removing {
            animation: slideOutRight 0.3s ease forwards;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .teacher-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
        }

        .teacher-item {
            text-align: center;
            font-size: 16px;
            color: #444;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .teacher-item:hover {
            background-color:rgba(239, 239, 239, 0.75);
        }

        .no-data {
            text-align: center;
            color: #888;
            font-size: 14px;
        }

        .modal {
            z-index: 1050;
        }

        .modal-backdrop {
            z-index: 1040;
        }
        
        .teacher-item-clickable {
            text-align: center;
            font-size: 16px;
            color: #444;
            cursor: pointer;
            transition: background-color 0.2s;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .teacher-item-clickable:hover {
            background-color: #e2e6ea;
        }

        .no-data {
            text-align: center;
            color: #888;
            font-size: 14px;
        }

        /* General styles for teacher grids */
        .teacher-grid {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
            padding: 20px 0;
        }

        /* Styles for teacher-usernames-grid */
        #teacher-usernames-grid {
            display: flex;
            flex-wrap: wrap; /* Prevents wrapping to new lines */
        }

        #teacher-usernames-grid .teacher-item-clickable {
            padding: 12px 16px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #1a202c;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-width: auto;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            white-space: nowrap; /* Prevents text wrapping within the item */
        }

        #teacher-usernames-grid .teacher-item-clickable:hover {
            background: linear-gradient(90deg, #eff6ff 0%, #ffffff 100%);
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
        }

        #teacher-usernames-grid .no-data {
            text-align: center;
            color: #64748b;
            font-size: 1rem;
            padding: 2rem;
            font-weight: 500;
        }

        /* Styles for teacher-usernames-card */
        #teacher-usernames-card {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            padding: 10px 0;
            max-width: 100%;
            margin: 0 auto;
            justify-content: flex-start;
        }

        #teacher-usernames-card .grid-item {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 282px;
            border-radius: 8px;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
        }

        #teacher-usernames-card .teacher-item-clickable:hover {
            transform: translateY(-3px);
            border-color: #667eea;
            box-shadow: 0 6px 15px rgba(102, 126, 234, 0.2);
        }

        .teacher-card-content {
            display: flex;
            flex-direction: column;
            width: 100%;
            gap: 12px;
        }

        .teacher-header {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .teacher-icon {
            font-size: 1.75rem;
            color: #667eea;
            background: #eff6ff;
            padding: 10px;
            border-radius: 50%;
            margin-right: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .teacher-username {
            font-size: 1.2rem;
            font-weight: 600;
            color: #1a202c;
            line-height: 1.4;
        }

        .teacher-details-right {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: flex-start;
            width: 100%;
        }

        .teacher-detail-item {
            display: flex;
            align-items: center;
            font-size: 0.9rem;
            color: #4a5568;
        }

        .detail-label {
            font-weight: 500;
            color: #64748b;
            min-width: 100px;
            margin-right: 8px;
            text-align: left;
        }

        .detail-value {
            color: #2d3748;
            font-weight: 400;
            word-break: break-word;
            max-width: auto;
        }

        #teacher-usernames-card .no-data {
            text-align: center;
            color: #64748b;
            font-size: 1rem;
            padding: 2rem;
            font-weight: 500;
            width: 100%;
        }

        /* Desktop-specific enhancements (1024px and above) */
        @media (min-width: 1024px) {
            #teacher-usernames-card .teacher-item-clickable {
                min-height: 220px;
            }

            .teacher-icon {
                font-size: 2rem;
                padding: 12px;
            }

            .teacher-username {
                font-size: 1.3rem;
            }

            .teacher-detail-item {
                font-size: 1rem;
            }

            .detail-label {
                min-width: 0px;
            }

            .detail-value {
                max-width: auto;
            }
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 1200px) {
            .main-wrapper {
                margin-left: 0;
            }
            
            .modal-dialog {
                max-width: 90% !important;
                width: 90% !important;
            }

            .modal-dialog .modal-content {
                width: 100% !important;
                height: auto !important;
                max-height: 80vh !important;
            }
        }

        @media (max-width: 992px) {
            .content {
                padding: 0.7rem;
            }
            
            .form-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1rem;
            }
            
            .grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 1rem;
            }

            .schedule-grid {
                grid-template-columns: 60px repeat(7, 1fr);
                min-width: 600px;
            }

            .schedule-header {
                font-size: 0.75rem;
                padding: 0.5rem 0.25rem;
            }

            .modal-dialog {
                max-width: 95% !important;
                width: 95% !important;
            }

            .modal-dialog .modal-content {
                height: auto !important;
                max-height: 85vh !important;
            }
        }

        @media (max-width: 768px) {
            .teacher-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .teacher-actions {
                width: 100%;
            }
            
            .teacher-actions .btn {
                width: 100%;
            }

            #teacher-usernames-card .teacher-item-clickable {
                width: 100%;
                min-height: 280px;
            }

            .teacher-details-right {
                align-items: flex-start;
            }

            .teacher-detail-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .detail-label {
                min-width: auto;
                margin-right: 0;
                text-align: left;
            }

            .detail-value {
                max-width: 100%;
            }

            .schedule-grid {
                grid-template-columns: 50px repeat(7, 1fr);
                min-width: 500px;
                gap: 4px;
                padding: 1rem;
            }

            .schedule-header {
                font-size: 0.7rem;
                padding: 0.4rem 0.2rem;
            }

            .schedule-time {
                font-size: 0.8rem;
                padding: 0.5rem 0.25rem;
            }

            .schedule-cell {
                min-height: 40px;
                font-size: 0.75rem;
            }

            .modal-dialog {
                max-width: 98% !important;
                width: 98% !important;
                margin: 1rem auto;
            }

            .modal-dialog .modal-content {
                height: auto !important;
                max-height: 90vh !important;
            }

            .modal-dialog .modal-body {
                padding: 1rem;
            }

            .modal-dialog .modal-header,
            .modal-dialog .modal-footer {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {

            .btn-cedvel{
                top: -10px;
                left: -5%;
                transform:scale(0.85);
            }
        .nav-container {
            position: relative;
            top: 65px;
            z-index: 9;
        }
        
        .nav-tab {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.57rem 1.5rem;
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 1.2rem;
            font-weight: 500;
            font-size: 0.62rem;
            white-space: nowrap;
            position: relative;
        }

            #teacher-usernames-card .teacher-item-clickable {
                padding: 1rem;
                min-height: 250px;
            }

            .teacher-icon {
                font-size: 1.5rem;
                padding: 8px;
            }

            .teacher-username {
                font-size: 1.1rem;
            }

            .teacher-detail-item {
                font-size: 0.85rem;
            }

            .teacher-grid {
                display: inline;
            }

            .detail-value {
                max-width: 150px;
            }

            .schedule-grid {
                min-width: 400px;
                gap: 2px;
                padding: 0.5rem;
            }

            .schedule-header {
                font-size: 0.65rem;
                padding: 0.3rem 0.1rem;
            }

            .schedule-time {
                font-size: 0.7rem;
                padding: 0.4rem 0.2rem;
            }

            .schedule-cell {
                min-height: 35px;
                font-size: 0.7rem;
            }
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 20px;
            background-color: #ffffff;
        }

        .card-header {
            background: linear-gradient(90deg, #f8fafc 0%, #ffffff 100%);
            border-bottom: 1px solid #e2e8f0;
            border-radius: 8px 8px 0 0;
            padding: 15px 20px;
        }

        .card-header h3 {
            margin: 0;
            font-size: 1.25rem;
            color: #1a202c;
            font-weight: 600;
        }

        .card-body {
            padding: 20px;
        }


        
        .temizle{
            color: white; 
            background:rgba(255, 0, 0, 1); 
            border-radius:6px; 
            cursor:pointer; 
            transition:0.42s;
            padding: 11px 13.5px; 
        }
        
        .temizle:hover{
                     border-radius:6px; 
            cursor:pointer; 
            transition:0.42s;
            transform:scale(1.05);
            background:rgba(255, 0, 0, 1); 
        }

        .cedvel_telebe{
            color: white; 
            background:rgba(59, 178, 243, 1); 
            border-radius:6px; 
            margin-right: 5px;
            cursor:pointer; 
            transition:0.42s;
            padding: 8px 10.5px; 
        }
        
        .cedvel_telebe:hover{
            transform:scale(1.05);
           background:rgba(59, 178, 243, 1); 
        }



            .temizle {
            height: 40px;
            width: 120px;
            color: white;
            background: #0e75acff;

            }

            .temizle:hover {
                background: #1f94d3ff;
            color: white;
            }


        .yadda_saxla{
            color: white; 
            background:rgba(15, 182, 91, 1); 
            border-radius:6px; 
            margin-right: 5px;
            cursor:pointer; 
            transition:0.42s;
            padding: 8px 10.5px; 
        }
        
        .yadda_saxla:hover{
            transform:scale(1.05);
            background:rgba(1, 170, 77, 1); 
        }



        .bagla{
            color: white; 
            background:rgba(247, 0, 0, 0.72); 
            border-radius:6px; 
            cursor:pointer; 
            transition:0.42s;
            padding: 8px 10.5px; 
        }
        
        .bagla:hover{
            transform:scale(1.05);
           background:rgba(237, 0, 0, 1); 
        }



        .telebe_add{
            color: white; 
            background:rgb(115, 125, 218); 
            border-radius:6px; 
            cursor:pointer; 
            transition:0.42s;
            padding: 8px 10.5px; 
        }
        
        .telebe_add:hover{
            transform:scale(1.05);
            background:rgba(28, 174, 121, 1); 
        }

        .custom-teacher-select {
            width: 100%;
            padding: 12px 16px;
            font-size: 14px;
            font-family: Arial, sans-serif;
            color: #333;
            background-color: #f8fafc;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            outline: none;
            appearance: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .custom-teacher-select:hover {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .custom-teacher-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130,246, 0.3);
        }

        .custom-teacher-select option {
            padding: 10px;
            font-size: 16px;
            background-color: #ffffff;
            color: #333;
        }

        .custom-teacher-select option:hover {
            background-color: #eff6ff;
        }

        .teacher-select-label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
        }
    </style>
</head>
<body>
    <?php include('filiallar/filial_modals.php'); ?>
    <script src="../assets/libs/jquery/dist/jquery.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="../dist/js/app-style-switcher.js"></script>
    <script src="../dist/js/feather.min.js"></script>
    <script src="../assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
    <script src="../dist/js/sidebarmenu.js"></script>
    <script src="../dist/js/custom.min.js"></script>
    <script src="filiallar/main.js"></script>
</body>
</html>