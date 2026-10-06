/**
 * The newsletter form: sends the subscription to the REST route and answers in
 * place, instead of leaving the page.
 *
 * Without this script the form still works — it posts to admin-post.php and
 * comes back with the result in the address (inc/newsletter.php). Here the
 * same fields go to apit/v1/newsletter, which hands them to Gravity Forms.
 *
 * The browser's own validation bubbles are off (novalidate on the form), so the
 * required fields are checked here and the message goes in the line under the
 * form, the one place the design has room for it.
 */
( function () {
	'use strict';

	var formularios = document.querySelectorAll( '.newsletter__form[data-rota]' );

	if ( ! formularios.length || ! window.fetch ) {
		return;
	}

	formularios.forEach( function ( form ) {
		var estado = form.querySelector( '.newsletter__estado' );
		var botao = form.querySelector( '.newsletter__submit' );

		function mostrar( texto, erro ) {
			estado.textContent = texto;
			estado.hidden = ! texto;
			estado.classList.toggle( 'is-erro', !! erro );
		}

		function marcar( erros ) {
			form.querySelectorAll( 'input[name]' ).forEach( function ( campo ) {
				if ( Object.prototype.hasOwnProperty.call( erros, campo.name ) ) {
					campo.setAttribute( 'aria-invalid', 'true' );
				} else {
					campo.removeAttribute( 'aria-invalid' );
				}
			} );
		}

		form.addEventListener( 'submit', function ( evento ) {
			evento.preventDefault();

			// The browser's check, without its bubbles: the first field that fails.
			var invalido = form.querySelector( 'input:invalid' );

			if ( invalido ) {
				var erros = {};
				form.querySelectorAll( 'input:invalid' ).forEach( function ( c ) {
					erros[ c.name ] = true;
				} );
				marcar( erros );
				mostrar( invalido.type === 'checkbox'
					? 'É preciso aceitar os termos para subscrever.'
					: ( invalido.type === 'email' && invalido.value ? 'Indique um email válido.' : 'Preencha os campos obrigatórios.' ), true );
				invalido.focus();
				return;
			}

			var dados = {};
			new FormData( form ).forEach( function ( valor, nome ) {
				dados[ nome ] = valor;
			} );

			botao.disabled = true;
			form.setAttribute( 'aria-busy', 'true' );
			mostrar( '', false );

			fetch( form.getAttribute( 'data-rota' ), {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( dados ),
				credentials: 'same-origin'
			} )
				.then( function ( resposta ) {
					return resposta.json();
				} )
				.then( function ( r ) {
					marcar( r.erros || {} );
					mostrar( r.mensagem || '', ! r.ok );

					if ( r.ok ) {
						form.reset();
					}
				} )
				.catch( function () {
					mostrar( 'Não foi possível concluir a inscrição. Tente mais tarde.', true );
				} )
				.then( function () {
					botao.disabled = false;
					form.removeAttribute( 'aria-busy' );
				} );
		} );
	} );
}() );
