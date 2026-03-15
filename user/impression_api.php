<?php
/* impression_api.php */
session_start();
include "../config/db.php";
if (!isset($_SESSION['user_id'])) { http_response_code(403); exit; }

$me = (int)$_SESSION['user_id'];
$d  = json_decode(file_get_contents('php://input'), true);
if (!$d) exit;

$post_id  = (int)($d['post_id']  ?? 0);
$action   = in_array($d['action']??'', ['view','like','reply','share','hide','report'])
            ? $d['action'] : 'view';
$dwell_ms = min((int)($d['dwell_ms'] ?? 0), 300000);
if (!$post_id) exit;

require_once __DIR__ . '/feed_ranker.php';
$ranker = new FeedRanker($conn, $me);
$ranker->logImpression($post_id, $action, $dwell_ms);
http_response_code(204);
