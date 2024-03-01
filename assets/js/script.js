document.addEventListener( 'DOMContentLoaded', ( event ) => {
	if ( document.querySelector( '#test-ui' ) !== null ) {
		if ( document.querySelector( '.test-form-new' ) !== null ) {
			document.querySelectorAll( '.test-form-new' ).forEach( ( e ) => {
				e.addEventListener( 'submit', ( event2 ) => {
					let url = event2.target.getAttribute( 'action' ) + event2.target.querySelector( '[name="board"]' ).value;
					event2.submitter.setAttribute( 'formaction', url );
				} );
			} );
		}

		if ( document.querySelector( '.test-form' ) !== null ) {
			document.querySelectorAll( '.test-form' ).forEach( ( e ) => {
				e.addEventListener( 'submit', ( event2 ) => {
					event2.preventDefault();

					let url = event2.target.getAttribute( 'action' ) + event2.target.querySelector( '[name="board"]' ).value;

					fetch( url, {
						'method': event2.target.getAttribute( 'method' ).toUpperCase(),
						'body': new URLSearchParams( new FormData( event2.target ) ),
					} )
					.then( ( response ) => {
						if ( response['ok'] ) {
							return response.text();
						} else {
							throw 'Error';
						}
					} )
					.then( ( data ) => {
						try {
							data = JSON.parse( data );
						} catch ( error ) {
							// console.log( error );
						}

						alert( data['error'] !== undefined ? data['error'] : data['result'] );
					} )
					.catch( ( error ) => {
						console.log( error );
					} );
				} );
			} );
		}
	} else if ( document.querySelector( '#board-title' ) !== null ) {
		let path = window.location.pathname.match( /(\/.*)*\/b\/([A-Za-z0-9]+)\/?$/ );
		// var path_prefix = window.location.pathname.replace( /(\/.*)*\/b\/([A-Za-z0-9]+)\/?$/, '$1/' );
		var path_prefix = path[1] + '/';
		var board = path[2];
		var url = `${path_prefix}api/threads/${board}?limit=0`;

		document.querySelector( '#board-title' ).innerHTML = `Welcome to <a href="${path_prefix}">/</a><a href="${path_prefix}b/${board}">b/${board}</a>`;

		fetch( url, {
			'method': 'GET',
		} )
		.then( ( response ) => {
			if ( response['ok'] ) {
				return response.json();
			} else {
				throw 'Error';
			}
		} )
		.then( ( data ) => {
			let html = '';

			data.forEach( ( thread ) => {
				html += `
				<div class="card thread mb-2">
					<h5 class="card-header">${thread['text']}</h5>
					<div class="card-body">
						<div class="replies">
				`;

				let hiddenCount = thread['replycount'] - 3;
				if (hiddenCount < 1) {
					hiddenCount = 0;
				}

				html += `<p class="card-text">${thread['replycount']} replies total (${hiddenCount} hidden) - <a href="${path_prefix}b/${board}/${thread['id']}">See the full thread here</a>.</p>`;

				thread['replies'].forEach( ( reply, index ) => {
					reply['id'] = index;

					html += `
							<div class="card reply mb-2">
								<div class="card-body">
									<p class="card-text">${reply['text']}</p>
								</div>
								<div class="card-footer text-muted">
									<div class="row align-items-center">
										<div class="col">
											<p class="mb-2"><span class="fw-semibold">Created on:</span> <span>${reply['created_on']}</span></p>
											<p class="id m-0"><span class="fw-semibold">Reply ID:</span> <span>${reply['id']}</span></p>
										</div>
										<div class="col d-flex justify-content-end">
											<form class="board-form me-4" action="${path_prefix}api/replies/${board}" method="put">
												<input type="hidden" name="thread_id" value="${thread['id']}">
												<input type="hidden" name="reply_id" value="${reply['id']}">
												<input class="btn btn-warning" type="submit" value="Report reply">
											</form>
											<form class="board-form d-flex" action="${path_prefix}api/replies/${board}" method="delete">
												<input type="hidden" name="thread_id" value="${thread['id']}" required>
												<input type="hidden" name="reply_id" value="${reply['id']}" required>
												<input class="form-control me-2" type="text" name="delete_password" placeholder="Password to delete" required>
												<input class="btn btn-danger" type="submit" value="Delete reply">
											</form>
										</div>
									</div>
								</div>
							</div>
					`;
				} );

				html += `
							<div class="mt-3">
								<form class="text-center" action="${path_prefix}api/replies/${board}" method="post">
									<input type="hidden" name="thread_id" value="${thread['id']}">
									<textarea class="form-control mb-2" rows="5" cols="80" name="text" placeholder="Quick reply..." required></textarea>
									<input class="form-control mb-2" type="text" name="delete_password" placeholder="Password to delete" required>
									<input class="btn btn-primary" type="submit" value="Submit">
								</form>
							</div>
						</div>
					</div>
					<div class="card-footer text-muted">
						<div class="row align-items-center">
							<div class="col">
								<p class="mb-2"><span class="fw-semibold">Created on:</span> <span>${thread['created_on']}</span></p>
								<p class="m-0"><span class="fw-semibold">Thread ID:</span> <span>${thread['id']}</span></p>
							</div>
							<div class="col d-flex justify-content-end">
								<form class="board-form me-4" action="${path_prefix}api/threads/${board}" method="put">
									<input type="hidden" name="thread_id" value="${thread['id']}">
									<input class="btn btn-warning" type="submit" value="Report thread">
								</form>
								<form class="board-form d-flex" action="${path_prefix}api/threads/${board}" method="delete">
									<input type="hidden" name="thread_id" value="${thread['id']}" required>
									<input class="form-control me-2" type="text" name="delete_password" placeholder="Password to delete" required>
									<input class="btn btn-danger" type="submit" value="Delete thread">
								</form>
							</div>
						</div>
					</div>
				</div>
				`;
			} );

			document.querySelector( '#board-display' ).innerHTML = html;
		} )
		.catch( ( error ) => {
			console.log( error );
		} );

		document.querySelector( '#new-thread' ).addEventListener( 'submit', ( event2 ) => {
			event2.target.setAttribute( 'action', `${path_prefix}api/threads/${board}` );
		} );

		document.querySelector( '#board-display' ).addEventListener( 'submit', ( event2 ) => {
			if ( event2.target.closest( '.board-form' ) ) {
				event2.preventDefault();

				let url = event2.target.closest( '.board-form' ).getAttribute( 'action' );

				fetch( url, {
					'method': event2.target.closest( '.board-form' ).getAttribute( 'method' ).toUpperCase(),
					'body': new URLSearchParams( new FormData( event2.target.closest( '.board-form' ) ) ),
				} )
				.then( ( response ) => {
					if ( response['ok'] ) {
						return response.text();
					} else {
						throw 'Error';
					}
				} )
				.then( ( data ) => {
					try {
						data = JSON.parse( data );
					} catch ( error ) {
						// console.log( error );
					}

					alert( data['error'] !== undefined ? data['error'] : data['result'] );

					if ( data['result'] == 'Successfully deleted' && ( event2.submitter.value == 'Delete reply' || event2.submitter.value == 'Delete thread' ) ) {
						window.location.reload();
					}
				} )
				.catch( ( error ) => {
					console.log( error );
				} );
			}
		} );
	} else if ( document.querySelector( '#thread-title' ) !== null ) {
		let path = window.location.pathname.match( /(\/.*)*\/b\/([A-Za-z0-9]+)\/([A-Za-z0-9]+)\/?$/ );
		var path_prefix = path[1] + '/';
		var board = path[2];
		var thread = path[3];
		var url = `${path_prefix}api/replies/${board}`;

		document.querySelector( '#thread-title' ).innerHTML = `Welcome to <a href="${path_prefix}">/</a><a href="${path_prefix}b/${board}">b/${board}</a><a href="${path_prefix}b/${board}/${thread}">/${thread}</a>`;

		fetch( url + '?' + new URLSearchParams( { 'thread_id': thread, 'limit': 0 } ).toString(), {
			'method': 'GET',
		} )
		.then( ( response ) => {
			if ( response['ok'] ) {
				return response.text();
			} else {
				throw 'Error';
			}
		} )
		.then( ( data ) => {
			try {
				thread = JSON.parse( data );
			} catch ( error ) {
				// throw error;

				// Redirect to board on error (thread does not exist)
				window.location.href = `${path_prefix}b/${board}`;
				return;
			}

			if ( ! Object.keys( thread ).length ) {
				// Redirect to board if thread does not exist
				window.location.href = `${path_prefix}b/${board}`;
				return;
			}

			let html = '';

			html += `
			<div class="card thread mb-2">
				<h5 class="card-header">${thread['text']}</h5>
				<div class="card-body">
					<div class="replies">
			`;

			thread['replies'].forEach( ( reply, index ) => {
				reply['id'] = index;

				html += `
						<div class="card reply mb-2">
							<div class="card-body">
								<p class="card-text">${reply['text']}</p>
							</div>
							<div class="card-footer text-muted">
								<div class="row align-items-center">
									<div class="col">
										<p class="mb-2"><span class="fw-semibold">Created on:</span> <span>${reply['created_on']}</span></p>
										<p class="id m-0"><span class="fw-semibold">Reply ID:</span> <span>${reply['id']}</span></p>
									</div>
									<div class="col d-flex justify-content-end">
										<form class="board-form me-4" action="${path_prefix}api/replies/${board}" method="put">
											<input type="hidden" name="thread_id" value="${thread['id']}">
											<input type="hidden" name="reply_id" value="${reply['id']}">
											<input class="btn btn-warning" type="submit" value="Report reply">
										</form>
										<form class="board-form d-flex" action="${path_prefix}api/replies/${board}" method="delete">
											<input type="hidden" name="thread_id" value="${thread['id']}" required>
											<input type="hidden" name="reply_id" value="${reply['id']}" required>
											<input class="form-control me-2" type="text" name="delete_password" placeholder="Password to delete" required>
											<input class="btn btn-danger" type="submit" value="Delete reply">
										</form>
									</div>
								</div>
							</div>
						</div>
				`;
			} );

			html += `
						<div class="mt-3">
							<form class="text-center" action="${path_prefix}api/replies/${board}" method="post">
								<input type="hidden" name="thread_id" value="${thread['id']}">
								<textarea class="form-control mb-2" rows="5" cols="80" name="text" placeholder="Quick reply..." required></textarea>
								<input class="form-control mb-2" type="text" name="delete_password" placeholder="Password to delete" required>
								<input class="btn btn-primary" type="submit" value="Submit">
							</form>
						</div>
					</div>
				</div>
				<div class="card-footer text-muted">
					<div class="row align-items-center">
						<div class="col">
							<p class="mb-2"><span class="fw-semibold">Created on:</span> <span>${thread['created_on']}</span></p>
							<p class="m-0"><span class="fw-semibold">Thread ID:</span> <span>${thread['id']}</span></p>
						</div>
						<div class="col d-flex justify-content-end">
							<form class="board-form me-4" action="${path_prefix}api/threads/${board}" method="put">
								<input type="hidden" name="thread_id" value="${thread['id']}">
								<input class="btn btn-warning" type="submit" value="Report thread">
							</form>
							<form class="board-form d-flex" action="${path_prefix}api/threads/${board}" method="delete">
								<input type="hidden" name="thread_id" value="${thread['id']}" required>
								<input class="form-control me-2" type="text" name="delete_password" placeholder="Password to delete" required>
								<input class="btn btn-danger" type="submit" value="Delete thread">
							</form>
						</div>
					</div>
				</div>
			</div>
			`;

			document.querySelector( '#board-display' ).innerHTML = html;
		} )
		.catch( ( error ) => {
			console.log( error );
		} );

		document.querySelector( '#board-display' ).addEventListener( 'submit', ( event2 ) => {
			if ( event2.target.closest( '.board-form' ) ) {
				event2.preventDefault();

				let url = event2.target.closest( '.board-form' ).getAttribute( 'action' );

				fetch( url, {
					'method': event2.target.closest( '.board-form' ).getAttribute( 'method' ).toUpperCase(),
					'body': new URLSearchParams( new FormData( event2.target.closest( '.board-form' ) ) ),
				} )
				.then( ( response ) => {
					if ( response['ok'] ) {
						return response.text();
					} else {
						throw 'Error';
					}
				} )
				.then( ( data ) => {
					try {
						data = JSON.parse( data );
					} catch ( error ) {
						// console.log( error );
					}

					alert( data['error'] !== undefined ? data['error'] : data['result'] );

					if ( data['result'] == 'Successfully deleted' && ( event2.submitter.value == 'Delete reply' || event2.submitter.value == 'Delete thread' ) ) {
						window.location.reload();
					}
				} )
				.catch( ( error ) => {
					console.log( error );
				} );
			}
		} );
	}
} );