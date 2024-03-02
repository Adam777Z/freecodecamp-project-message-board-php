<?php
$path_prefix = '';

if ( isset( $_SERVER['PATH_INFO'] ) ) {
	$path_count = substr_count( $_SERVER['PATH_INFO'], '/' ) - 1;

	for ( $i = 0; $i < $path_count; $i++ ) {
		$path_prefix .= '../';
	}

	if ( strpos( $_SERVER['PATH_INFO'], '/api/threads' ) !== false || strpos( $_SERVER['PATH_INFO'], '/api/replies' ) !== false ) {
		preg_match( '~\/api\/(?:(?:threads)|(?:replies))\/([A-Za-z0-9]+)\/?.*?~', $_SERVER['PATH_INFO'], $matches );
		$board = $matches[1];

		if ( empty( $board ) ) {
			header( 'Content-Type: application/json; charset=utf-8' );
			echo json_encode( [
				'error' => 'board is required',
			] );
			exit;
		}

		try {
			$db = new PDO( 'sqlite:database.db' );
		} catch ( PDOException $e ) {
			exit( $e->getMessage() );
		}
	}

	if ( strpos( $_SERVER['PATH_INFO'], '/b/' ) !== false ) {
		preg_match( '~\/b\/([A-Za-z0-9]+)\/?([A-Za-z0-9]+)?\/?.*?~', $_SERVER['PATH_INFO'], $matches );
		$board = isset( $matches[1] ) ? $matches[1] : '';
		$thread = isset( $matches[2] ) ? $matches[2] : '';
	} elseif ( strpos( $_SERVER['PATH_INFO'], '/api/threads' ) !== false ) {
		if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			$limit = isset( $_GET['limit'] ) && ! empty( $_GET['limit'] ) && is_numeric( $_GET['limit'] ) ? (int) $_GET['limit'] : 10;

			header( 'Content-Type: application/json; charset=utf-8' );
			echo json_encode( get_board_threads( $board, $limit ) );
			exit;
		} elseif ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			if ( empty( $_POST['text'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Text is required',
				] );
				exit;
			}

			if ( empty( $_POST['delete_password'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Delete password is required',
				] );
				exit;
			}

			$text = trim( htmlspecialchars( $_POST['text'] ) );
			$delete_password = $_POST['delete_password'];

			if ( add_thread( $board, $text, $delete_password ) ) {
				header( "Location: {$path_prefix}b/{$board}" );
				exit;
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Could not create the thread',
				] );
				exit;
			}
		} elseif ( $_SERVER['REQUEST_METHOD'] == 'PUT' ) {
			$input = file_get_contents( 'php://input' );
			parse_str( $input, $data );

			if ( empty( $data['thread_id'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Thread ID is required',
				] );
				exit;
			}

			if ( report_thread( $data['thread_id'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'result' => 'Reported',
				] );
				exit;
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Could not report',
				] );
				exit;
			}
		} elseif ( $_SERVER['REQUEST_METHOD'] == 'DELETE' ) {
			$input = file_get_contents( 'php://input' );
			parse_str( $input, $data );

			if ( empty( $data['thread_id'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Thread ID is required',
				] );
				exit;
			}

			if ( empty( $data['delete_password'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Delete password is required',
				] );
				exit;
			}

			if ( delete_thread( $data['thread_id'], $data['delete_password'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'result' => 'Successfully deleted',
				] );
				exit;
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Could not delete',
				] );
				exit;
			}
		} else {
			redirect_to_index();
		}
	} elseif ( strpos( $_SERVER['PATH_INFO'], '/api/replies' ) !== false ) {
		if ( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
			if ( empty( $_GET['thread_id'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Thread ID is required',
				] );
				exit;
			}

			$limit = isset( $_GET['limit'] ) && ( ! empty( $_GET['limit'] ) || $_GET['limit'] === '0' ) && is_numeric( $_GET['limit'] ) ? (int) $_GET['limit'] : 3;

			header( 'Content-Type: application/json; charset=utf-8' );
			echo json_encode( get_thread( $_GET['thread_id'], $limit ) );
			exit;
		} elseif ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			if ( empty( $_POST['thread_id'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Thread ID is required',
				] );
				exit;
			}

			if ( empty( $_POST['text'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Text is required',
				] );
				exit;
			}

			if ( empty( $_POST['delete_password'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Delete password is required',
				] );
				exit;
			}

			$thread_id = $_POST['thread_id'];
			$text = trim( htmlspecialchars( $_POST['text'] ) );
			$delete_password = $_POST['delete_password'];

			if ( add_reply( $thread_id, $text, $delete_password ) ) {
				header( "Location: {$path_prefix}b/{$board}/{$thread_id}" );
				exit;
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Could not add the reply',
				] );
				exit;
			}
		} elseif ( $_SERVER['REQUEST_METHOD'] == 'PUT' ) {
			$input = file_get_contents( 'php://input' );
			parse_str( $input, $data );

			if ( empty( $data['thread_id'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Thread ID is required',
				] );
				exit;
			}


			if ( empty( $data['reply_id'] ) && $data['reply_id'] !== '0' ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Reply ID is required',
				] );
				exit;
			}

			if ( report_reply( $data['thread_id'], $data['reply_id'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'result' => 'Reported',
				] );
				exit;
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Could not report',
				] );
				exit;
			}
		} elseif ( $_SERVER['REQUEST_METHOD'] == 'DELETE' ) {
			$input = file_get_contents( 'php://input' );
			parse_str( $input, $data );

			if ( empty( $data['thread_id'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Thread ID is required',
				] );
				exit;
			}

			if ( empty( $data['reply_id'] ) && $data['reply_id'] !== '0' ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Reply ID is required',
				] );
				exit;
			}

			if ( empty( $data['delete_password'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Delete password is required',
				] );
				exit;
			}

			if ( delete_reply( $data['thread_id'], $data['reply_id'], $data['delete_password'] ) ) {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'result' => 'Successfully deleted',
				] );
				exit;
			} else {
				header( 'Content-Type: application/json; charset=utf-8' );
				echo json_encode( [
					'error' => 'Could not delete',
				] );
				exit;
			}
		} else {
			redirect_to_index();
		}
	} elseif ( strpos( $_SERVER['PATH_INFO'], '/api/test' ) !== false ) {
		$board = 'test';
		$tests = [];

		$send_data = [
			'text' => 'Text',
			'delete_password' => 'password',
		];
		$data = post_api_data( "/api/threads/$board", $send_data );
		$tests[] = [
			'title' => "API routing for /api/threads/$board: POST: Create 2 new threads (because 1 will be deleted in a test)",
			'data' => $send_data,
			'passed' => ! isset( $data['error'] ) || $data['error'] != 'Could not create the thread',
		];

		$send_data = [
			'text' => 'Text',
			'delete_password' => 'password',
		];
		$data = post_api_data( "/api/threads/$board", $send_data );
		$tests[] = [
			'title' => "API routing for /api/threads/$board: POST: Create 2 new threads (because 1 will be deleted in a test)",
			'data' => $send_data,
			'passed' => ! isset( $data['error'] ) || $data['error'] != 'Could not create the thread',
		];

		$send_data = [];
		$data = get_api_data( "/api/threads/$board?" . http_build_query( $send_data ) );
		$tests[] = [
			'title' => "API routing for /api/threads/$board: GET: Most recent 10 bumped threads with only the most recent 3 replies each",
			'data' => $send_data,
			'passed' => (
				is_array( $data )
				&&
				count( $data ) <= 10
				&&
				isset( $data[0]['id'] )
				&&
				isset( $data[0]['board'] )
				&&
				$data[0]['board'] == $board
				&&
				isset( $data[0]['text'] )
				&&
				isset( $data[0]['created_on'] )
				&&
				isset( $data[0]['bumped_on'] )
				&&
				isset( $data[0]['replies'] )
				&&
				is_array( $data[0]['replies'] )
				&&
				count( $data[0]['replies'] ) <= 3
				&&
				isset( $data[0]['replycount'] )
				&&
				! isset( $data[0]['delete_password'] )
				&&
				! isset( $data[0]['reported'] )
			),
		];
		$id1 = $data[0]['id'];
		$id2 = $data[1]['id'];

		$send_data = [
			'thread_id' => $id2,
		];
		$data = post_api_data( "/api/threads/$board", $send_data, 'PUT' );
		$tests[] = [
			'title' => "API routing for /api/threads/$board: PUT: Report thread",
			'data' => $send_data,
			'passed' => isset( $data['result'] ) && $data['result'] == 'Reported',
		];

		$send_data = [
			'thread_id' => $id1,
			'delete_password' => 'invalid',
		];
		$data = post_api_data( "/api/threads/$board", $send_data, 'DELETE' );
		$tests[] = [
			'title' => "API routing for /api/threads/$board: DELETE: Delete thread with invalid password",
			'data' => $send_data,
			'passed' => isset( $data['error'] ) && $data['error'] == 'Could not delete',
		];

		$send_data = [
			'thread_id' => $id1,
			'delete_password' => 'password',
		];
		$data = post_api_data( "/api/threads/$board", $send_data, 'DELETE' );
		$tests[] = [
			'title' => "API routing for /api/threads/$board: DELETE: Delete thread with valid password",
			'data' => $send_data,
			'passed' => isset( $data['result'] ) && $data['result'] == 'Successfully deleted',
		];

		$send_data = [
			'thread_id' => $id2,
			'text' => 'Reply',
			'delete_password' => 'password',
		];
		$data = post_api_data( "/api/replies/$board", $send_data );
		$tests[] = [
			'title' => "API routing for /api/replies/$board: POST: Reply to thread",
			'data' => $send_data,
			'passed' => ! isset( $data['error'] ) || $data['error'] != 'Could not add the reply',
		];

		$send_data = [
			'thread_id' => $id2,
		];
		$data = get_api_data( "/api/replies/$board?" . http_build_query( $send_data ) );
		$tests[] = [
			'title' => "API routing for /api/replies/$board: GET: Get all replies for a thread",
			'data' => $send_data,
			'passed' => (
				is_array( $data )
				&&
				isset( $data['id'] )
				&&
				isset( $data['board'] )
				&&
				$data['board'] == $board
				&&
				isset( $data['text'] )
				&&
				isset( $data['created_on'] )
				&&
				isset( $data['bumped_on'] )
				&&
				isset( $data['replies'] )
				&&
				is_array( $data['replies'] )
				&&
				isset( $data['replies'][ count( $data['replies'] ) - 1 ] )
				&&
				is_array( $data['replies'][ count( $data['replies'] ) - 1 ] )
				&&
				isset( $data['replies'][ count( $data['replies'] ) - 1 ]['id'] )
				&&
				isset( $data['replies'][ count( $data['replies'] ) - 1 ]['text'] )
				&&
				$data['replies'][ count( $data['replies'] ) - 1 ]['text'] == 'Reply'
				&&
				isset( $data['replies'][ count( $data['replies'] ) - 1 ]['created_on'] )
				&&
				! isset( $data['replies'][ count( $data['replies'] ) - 1 ]['delete_password'] )
				&&
				! isset( $data['replies'][ count( $data['replies'] ) - 1 ]['reported'] )
				&&
				isset( $data['replycount'] )
				&&
				! isset( $data['delete_password'] )
				&&
				! isset( $data['reported'] )
			),
		];
		$id3 = $data['replies'][ count( $data['replies'] ) - 1 ]['id'];

		$send_data = [
			'thread_id' => $id2,
			'reply_id' => $id3,
		];
		$data = post_api_data( "/api/replies/$board", $send_data, 'PUT' );
		$tests[] = [
			'title' => "API routing for /api/replies/$board: PUT: Report reply",
			'data' => $send_data,
			'passed' => isset( $data['result'] ) && $data['result'] == 'Reported',
		];

		$send_data = [
			'thread_id' => $id2,
			'reply_id' => $id3,
			'delete_password' => 'invalid',
		];
		$data = post_api_data( "/api/replies/$board", $send_data, 'DELETE' );
		$tests[] = [
			'title' => "API routing for /api/replies/$board: DELETE: Delete reply with invalid password",
			'data' => $send_data,
			'passed' => isset( $data['error'] ) && $data['error'] == 'Could not delete',
		];

		$send_data = [
			'thread_id' => $id2,
			'reply_id' => $id3,
			'delete_password' => 'password',
		];
		$data = post_api_data( "/api/replies/$board", $send_data, 'DELETE' );
		$tests[] = [
			'title' => "API routing for /api/replies/$board: DELETE: Delete reply with valid password",
			'data' => $send_data,
			'passed' => isset( $data['result'] ) && $data['result'] == 'Successfully deleted',
		];

		header( 'Content-Type: application/json; charset=utf-8' );
		echo json_encode( $tests );
		exit;
	} else {
		redirect_to_index();
	}
}

function redirect_to_index() {
	global $path_prefix;

	if ( $path_prefix == '' ) {
		$path_prefix = './';
	}

	header( 'Location: ' . $path_prefix );
	exit;
}

function get_api_data( $path ) {
	$url = 'http' . ( ! empty( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

	if ( isset( $_SERVER['PATH_INFO'] ) ) {
		$url = str_replace( $_SERVER['PATH_INFO'], '', $url ) . '/';
	}

	$url .= ltrim( $path, '/' );

	$ch = curl_init( $url );

	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

	$result = curl_exec( $ch );

	$return = $result ? json_decode( $result, true ) : [];

	curl_close( $ch );

	return $return;
}

function post_api_data( $path, $data, $method = 'POST' ) {
	$url = 'http' . ( ! empty( $_SERVER['HTTPS'] ) ? 's' : '' ) . '://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

	if ( isset( $_SERVER['PATH_INFO'] ) ) {
		$url = str_replace( $_SERVER['PATH_INFO'], '', $url ) . '/';
	}

	$url .= ltrim( $path, '/' );

	if ( $method != 'POST' ) {
		$data = http_build_query( $data );
	}

	$ch = curl_init( $url );

	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
	// curl_setopt( $ch, CURLOPT_POST, true );
	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );

	$result = curl_exec( $ch );

	$return = $result ? json_decode( $result, true ) : [];

	curl_close( $ch );

	return $return;
}

function get_board_threads( $board, $limit = false, $replies_reversed = true ) {
	global $db;

	$query = "SELECT * FROM messageboard WHERE board = {$db->quote( $board )} ORDER BY bumped_on DESC";

	if ( $limit ) {
		$query .= " LIMIT $limit";
	}

	$query = $db->query( $query );
	$result = $query->fetchAll( PDO::FETCH_ASSOC );

	if ( $result ) {
		foreach ( $result as $key => $value ) {
			$result[$key]['id'] = (int) $result[$key]['id'];

			unset( $result[$key]['delete_password'] );
			unset( $result[$key]['reported'] );

			$result[$key]['replies'] = json_decode( $result[$key]['replies'], true );

			if ( $replies_reversed ) {
				$result[$key]['replies'] = array_reverse( $result[$key]['replies'] );
			}

			$result[$key]['replycount'] = count( $result[$key]['replies'] );

			if ( $result[$key]['replycount'] > 3 ) {
				$result[$key]['replies'] = array_slice( $result[$key]['replies'], 0, 3 );
			}

			foreach ( $result[$key]['replies'] as $key2 => $value2 ) {
				unset( $result[$key]['replies'][$key2]['delete_password'] );
				unset( $result[$key]['replies'][$key2]['reported'] );
			}
		}
	}

	return $result ? $result : [];
}

function get_thread( $thread_id, $limit = false, $replies_reversed = true, $all = false ) {
	global $db;

	$query = $db->query( "SELECT * FROM messageboard WHERE id = {$db->quote( $thread_id )} ORDER BY created_on DESC" );
	$result = $query->fetchAll( PDO::FETCH_ASSOC );

	if ( $result ) {
		$key = 0;

		$result[$key]['id'] = (int) $result[$key]['id'];

		$result[$key]['replies'] = json_decode( $result[$key]['replies'], true );

		if ( $replies_reversed ) {
			$result[$key]['replies'] = array_reverse( $result[$key]['replies'] );
		}

		$result[$key]['replycount'] = count( $result[$key]['replies'] );

		if ( $limit && $result[$key]['replycount'] > $limit ) {
			$result[$key]['replies'] = array_slice( $result[$key]['replies'], 0, $limit );
		}

		if ( ! $all ) {
			unset( $result[$key]['delete_password'] );
			unset( $result[$key]['reported'] );

			foreach ( $result[$key]['replies'] as $key2 => $value2 ) {
				unset( $result[$key]['replies'][$key2]['delete_password'] );
				unset( $result[$key]['replies'][$key2]['reported'] );
			}
		}
	}

	return $result ? $result[0] : [];
}

function add_thread( $board, $text, $delete_password ) {
	global $db;

	$date = date_create( 'now', timezone_open( 'UTC' ) );
	$date = date_format( $date, 'Y-m-d\\TH:i:s.vP' );

	$data = [
		'board' => $board,
		'text' => $text,
		'delete_password' => $delete_password,
		'created_on' => $date,
		'bumped_on' => $date,
		'reported' => (int) false,
		'replies' => json_encode( [] ),
	];
	$sth = $db->prepare( 'INSERT INTO messageboard (board, text, delete_password, created_on, bumped_on, reported, replies) VALUES (:board, :text, :delete_password, :created_on, :bumped_on, :reported, :replies)' );
	return $sth->execute( $data );
}

function report_thread( $thread_id ) {
	global $db;

	$data = [
		'id' => (int) $thread_id,
		'reported' => (int) true,
	];
	$sth = $db->prepare( 'UPDATE messageboard SET reported = :reported WHERE id = :id' );
	return $sth->execute( $data );
}

function delete_thread( $thread_id, $delete_password ) {
	global $db;

	$data = [
		'id' => (int) $thread_id,
		'delete_password' => $delete_password,
	];
	$sth = $db->prepare( 'DELETE FROM messageboard WHERE id = :id AND delete_password = :delete_password' );
	$sth->execute( $data );
	return (bool) $sth->rowCount();
}

function add_reply( $thread_id, $text, $delete_password ) {
	global $db;

	$thread = get_thread( $thread_id, false, false, true );

	if ( $thread ) {
		$date = date_create( 'now', timezone_open( 'UTC' ) );
		$date = date_format( $date, 'Y-m-d\\TH:i:s.vP' );

		// $reply_id = count( $thread['replies'] ) > 0 ? $thread['replies'][ count( $thread['replies'] ) - 1 ]['id'] + 1 : 1;
		$reply_id = count( $thread['replies'] ) + 1;

		$thread['replies'][] = [
			'id' => $reply_id,
			'text' => $text,
			'delete_password' => $delete_password,
			'created_on' => $date,
			'reported' => (int) false,
		];

		$data = [
			'id' => (int) $thread_id,
			'bumped_on' => $date,
			'replies' => json_encode( $thread['replies'] ),
		];
		$sth = $db->prepare( 'UPDATE messageboard SET bumped_on = :bumped_on, replies = :replies WHERE id = :id' );
		return $sth->execute( $data );
	} else {
		return false;
	}
}

function report_reply( $thread_id, $reply_id ) {
	global $db;

	$thread = get_thread( $thread_id, false, false, true );

	if ( $thread ) {
		foreach ( $thread['replies'] as $key => $value ) {
			if ( $thread['replies'][$key]['id'] == $reply_id ) {
				$thread['replies'][$key]['reported'] = (int) true;

				$data = [
					'id' => (int) $thread_id,
					'replies' => json_encode( $thread['replies'] ),
				];
				$sth = $db->prepare( 'UPDATE messageboard SET replies = :replies WHERE id = :id' );
				return $sth->execute( $data );
			}
		}
	}

	return false;
}

function delete_reply( $thread_id, $reply_id, $delete_password ) {
	global $db;

	$thread = get_thread( $thread_id, false, false, true );

	if ( $thread ) {
		foreach ( $thread['replies'] as $key => $value ) {
			if ( $thread['replies'][$key]['id'] == $reply_id && $thread['replies'][$key]['delete_password'] == $delete_password ) {
				$thread['replies'][$key]['text'] = '[deleted]';

				$data = [
					'id' => (int) $thread_id,
					'replies' => json_encode( $thread['replies'] ),
				];
				$sth = $db->prepare( 'UPDATE messageboard SET replies = :replies WHERE id = :id' );
				return $sth->execute( $data );
			}
		}
	}

	return false;
}
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Anonymous Message Board</title>
	<meta name="description" content="freeCodeCamp - Information Security and Quality Assurance Project: Anonymous Message Board">
	<link rel="icon" type="image/x-icon" href="<?php echo $path_prefix; ?>favicon.ico">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/bootstrap.min.css">
	<link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/style.min.css">
	<script src="<?php echo $path_prefix; ?>assets/js/script.min.js"></script>
</head>
<body>
	<div class="container">
		<div class="p-4 my-4 bg-light rounded-3">
			<div class="row">
				<div class="col">
					<header>
						<h1 id="title" class="text-center">Anonymous Message Board</h1>
					</header>

					<?php if ( ! empty( $board ) && ! empty( $thread ) ) { ?>
						<h2 id="thread-title" class="text-center"></h2>

						<div id="board-display"></div>
					<?php } elseif ( ! empty( $board ) ) { ?>
						<h2 id="board-title" class="text-center"></h2>

						<div id="add-new-thread" class="mb-3">
							<h3>Add a new thread:</h3>
							<form id="new-thread" class="text-center" action="<?php echo $path_prefix; ?>api/" method="post">
								<textarea class="form-control mb-2" rows="8" cols="120" name="text" placeholder="Thread text..." required></textarea>
								<input class="form-control mb-2" type="text" name="delete_password" placeholder="Password to delete" autocomplete="off" required>
								<input class="btn btn-primary" type="submit" value="Submit">
							</form>
						</div>

						<div id="board-display"></div>
					<?php } else { ?>
					<div id="user-stories">
						<h3>User Stories:</h3>
						<ol>
							<li>I can <b>POST</b> a thread to a specific message board by passing form data <code>text</code> and <code>delete_password</code> to <i>/api/threads/{board}</i>. (Recommended: redirect to board page /b/{board})<br>
								Saved will be <code>id</code>, <code>text</code>, <code>created_on</code> (date &amp; time), <code>bumped_on</code> (date &amp; time, starts same as <code>created_on</code>), <code>reported</code> (boolean), <code>delete_password</code>, &amp; <code>replies</code> (array).</li>
							<li>I can <b>POST</b> a reply to a thread on a specific board by passing form data <code>text</code>, <code>delete_password</code>, &amp; <code>thread_id</code> to <i>/api/replies/{board}</i> and it will also update the <code>bumped_on</code> date to the comment's date. (Recommended: redirect to thread page /b/{board}/{thread_id})<br>
								In the thread's 'replies' array will be saved <code>id</code>, <code>text</code>, <code>created_on</code>, <code>delete_password</code>, &amp; <code>reported</code>.</li>
							<li>I can <b>GET</b> an array of the most recent 10 bumped threads on the board with only the most recent 3 replies from <i>/api/threads/{board}</i>. The <code>reported</code> and <code>delete_password</code> fields will not be sent. Also include <code>replycount</code> (total number of replies).</li>
							<li>I can <b>GET</b> an entire thread with all its replies from <i>/api/replies/{board}?thread_id={thread_id}</i>. Also hiding the same fields (<code>reported</code> and <code>delete_password</code>).</li>
							<li>I can delete a thread completely if I send a <b>DELETE</b> request to <i>/api/threads/{board}</i> and pass along the <code>thread_id</code> &amp; <code>delete_password</code>. (Text response will be 'Successfully deleted' or 'Could not delete')</li>
							<li>I can delete a post (just changing the text to '[deleted]') if I send a <b>DELETE</b> request to <i>/api/replies/{board}</i> and pass along the <code>thread_id</code>, <code>reply_id</code>, &amp; <code>delete_password</code>. (Text response will be 'Successfully deleted' or 'Could not delete')</li>
							<li>I can report a thread and change its reported value to true by sending a <b>PUT</b> request to <i>/api/threads/{board}</i> and pass along the <code>thread_id</code>. (Text response will be 'Reported' or 'Could not report')</li>
							<li>I can report a reply and change its reported value to true by sending a <b>PUT</b> request to <i>/api/replies/{board}</i> and pass along the <code>thread_id</code> &amp; <code>reply_id</code>. (Text response will be 'Reported' or 'Could not report')</li>
							<li>All 11 <a href="<?php echo $path_prefix; ?>api/test" target="_blank">tests</a> are complete and passing.</li>
						</ol>
						<div class="table-responsive-sm">
							<table class="table">
								<thead>
									<tr>
										<th scope="col">API</th>
										<th scope="col">GET</th>
										<th scope="col">POST</th>
										<th scope="col">PUT</th>
										<th scope="col">DELETE</th>
									</tr>
								</thead>
								<tbody>
									<tr>
										<th scope="row">/api/threads/{board}</th>
										<td>list recent threads</td>
										<td>create thread</td>
										<td>report thread</td>
										<td>delete thread with password</td>
									</tr>
									<tr>
										<th scope="row">/api/replies/{board}</th>
										<td>show all replies on thread</td>
										<td>create reply on thread</td>
										<td>report reply on thread</td>
										<td>change reply to '[deleted]' on thread with password</td>
									</tr>
								</tbody>
							</table>
						</div>
						<h3>Example GET usage:</h3>
						<ul>
							<li><code>/api/threads/{board}</code></li>
							<li><code>/api/replies/{board}?thread_id={thread_id}</code></li>
							<li><code><a href="<?php echo $path_prefix; ?>api/threads/general" target="_blank">/api/threads/general</a></code></li>
							<li><code>/api/replies/general?thread_id={thread_id}</code></li>
						</ul>
						<h2><a href="<?php echo $path_prefix; ?>b/general">Go to testing <i>'/b/general'</i> board</a></h2>
					</div>

					<hr>

					<div id="test-ui">
						<h2>API Tests:</h2>
						<div class="row text-center">
							<div class="col">
								<h4>New thread</h4>
								<h5>(POST /api/threads/:board)</h5>
								<form class="test-form-new" action="<?php echo $path_prefix; ?>api/threads/" method="post">
									<input type="text" class="form-control mb-2" name="board" placeholder="Board" required>
									<textarea class="form-control mb-2" name="text" placeholder="Thread text..." required></textarea>
									<input type="text" class="form-control mb-2" name="delete_password" placeholder="Password to delete" autocomplete="off" required>
									<input type="submit" class="btn btn-primary" value="Submit">
								</form>
							</div>
							<div class="col">
								<h4>Report thread</h4>
								<h5>(PUT /api/threads/:board)</h5>
								<form class="test-form" action="<?php echo $path_prefix; ?>api/threads/" method="put">
									<input type="text" class="form-control mb-2" name="board" placeholder="Board" required>
									<input type="text" class="form-control mb-2" name="thread_id" placeholder="ID of thread to report" required>
									<input type="submit" class="btn btn-warning" value="Submit">
								</form>
							</div>
							<div class="col">
								<h4>Delete thread</h4>
								<h5>(DELETE /api/threads/:board)</h5>
								<form class="test-form" action="<?php echo $path_prefix; ?>api/threads/" method="delete">
									<input type="text" class="form-control mb-2" name="board" placeholder="Board" required>
									<input type="text" class="form-control mb-2" name="thread_id" placeholder="ID of thread to delete" required>
									<input type="text" class="form-control mb-2" name="delete_password" placeholder="Password to delete" autocomplete="off" required>
									<input type="submit" class="btn btn-danger" value="Submit">
								</form>
							</div>
						</div>

						<hr>

						<div class="row text-center">
							<div class="col">
								<h4>New reply</h4>
								<h5>(POST /api/replies/:board)</h5>
								<form class="test-form-new" action="<?php echo $path_prefix; ?>api/replies/" method="post">
									<input type="text" class="form-control mb-2" name="board" placeholder="Board" required>
									<input type="text" class="form-control mb-2" name="thread_id" placeholder="Thread ID" required>
									<textarea class="form-control mb-2" name="text" placeholder="Reply text..." required></textarea>
									<input type="text" class="form-control mb-2" name="delete_password" placeholder="Password to delete" autocomplete="off" required>
									<input type="submit" class="btn btn-primary" value="Submit">
								</form>
							</div>
							<div class="col">
								<h4>Report reply</h4>
								<h5>(PUT /api/replies/:board)</h5>
								<form class="test-form" action="<?php echo $path_prefix; ?>api/replies/" method="put">
									<input type="text" class="form-control mb-2" name="board" placeholder="Board" required>
									<input type="text" class="form-control mb-2" name="thread_id" placeholder="Thread ID" required>
									<input type="text" class="form-control mb-2" name="reply_id" placeholder="ID of reply to report" required>
									<input type="submit" class="btn btn-warning" value="Submit">
								</form>
							</div>
							<div class="col">
								<h4>Delete reply</h4>
								<h5>(DELETE /api/replies/:board)</h5>
								<form class="test-form" action="<?php echo $path_prefix; ?>api/replies/" method="delete">
									<input type="text" class="form-control mb-2" name="board" placeholder="Board" required>
									<input type="text" class="form-control mb-2" name="thread_id" placeholder="Thread ID" required>
									<input type="text" class="form-control mb-2" name="reply_id" placeholder="ID of reply to delete" required>
									<input type="text" class="form-control mb-2" name="delete_password" placeholder="Password to delete" autocomplete="off" required>
									<input type="submit" class="btn btn-danger" value="Submit">
								</form>
							</div>
						</div>
					</div>
					<?php } ?>

					<hr>

					<div class="footer text-center">by <a href="https://www.freecodecamp.org" target="_blank">freeCodeCamp</a> (ISQA3) & <a href="https://www.freecodecamp.org/adam777" target="_blank">Adam</a> | <a href="https://github.com/Adam777Z/freecodecamp-project-message-board-php" target="_blank">GitHub</a></div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>