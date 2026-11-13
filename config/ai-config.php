<?php
/*
 * Al-Ahsa Vision Hub - Gemini API Configuration
 */

require_once __DIR__ . '/ai-secret.php';

define('GEMINI_MODEL', 'gemini-3.8-flash');

define(
    'GEMINI_API_URL',
    'https://generativelanguage.googleapis.com/v1beta/models/'
);