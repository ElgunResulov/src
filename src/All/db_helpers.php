<?php
/**
 * Helper functions for handling permissions
 */

/**
 * Safely encode permissions array to JSON
 * Prevents Unicode characters from being escaped
 */
function encodePermissions($permissions) {
    return json_encode($permissions, JSON_UNESCAPED_UNICODE);
}

/**
 * Safely decode JSON permissions to array
 * Handles both string and array inputs
 */
function decodePermissions($jsonPermissions) {
    if (empty($jsonPermissions)) {
        return [];
    }
    
    if (is_array($jsonPermissions)) {
        return $jsonPermissions;
    }
    
    $decoded = json_decode($jsonPermissions, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Get default permissions for a role
 */
function getDefaultPermissionsForRole($role) {
    $rolePermissions = [
        'teacher' => [
            'Əsas', 'Dərslər', 'Tələbələr', 'İmtahanlar', 'Dərs Cədvəli', 'Statistika'
        ],
        'student' => [
            'Əsas', 'Dərslər', 'İmtahanlar', 'Dərs Cədvəli'
        ],
        'staff' => [
            'Əsas', 'Müəllimlər', 'Dərslər', 'Tələbələr', 'İmtahanlar', 'Dərs Cədvəli', 'Əməkdaşlar'
        ],
        'parent' => [
            'Əsas', 'Dərslər', 'İmtahanlar', 'Dərs Cədvəli'
        ],
        'examiner' => [
            'Əsas', 'İmtahanlar', 'Statistika'
        ]
    ];
    
    return isset($rolePermissions[$role]) ? $rolePermissions[$role] : [];
}
?>

