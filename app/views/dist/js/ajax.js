/* Enviar formularios via AJAX */
const formularios_ajax = document.querySelectorAll(".FormularioAjax");

formularios_ajax.forEach(formularios => {

    formularios.addEventListener("submit", function (e) {

        e.preventDefault();

        // Verificar si el formulario tiene el atributo para recargar directo
        if (this.hasAttribute("data-recargar-directo")) {
            let data = new FormData(this);
            let method = this.getAttribute("method");
            let action = this.getAttribute("action");

            let encabezados = new Headers();

            let config = {
                method: method,
                headers: encabezados,
                mode: 'cors',
                cache: 'no-cache',
                body: data
            };

            fetch(action, config)
                .then(respuesta => respuesta.json())
                .then(respuesta => {
                    return alertas_ajax(respuesta);
                    // ✅ SOLUCIÓN AÑADIDA: recargar la página completa
                    setTimeout(() => {
                        location.reload();
                    }, 500); // opcional: pequeña espera para que se vea el mensaje
                });

            return; // Salir antes de mostrar la alerta
        }

        Swal.fire({
            title: '¿Está seguro?',
            text: "Desea realizar la acción solicitada",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3e80c1',
            cancelButtonColor: '#844c4f',
            confirmButtonText: 'Si, realizar',
            cancelButtonText: 'No, cancelar'
        }).then((result) => {
            if (result.isConfirmed) {

                let data = new FormData(this);
                let method = this.getAttribute("method");
                let action = this.getAttribute("action");

                let encabezados = new Headers();

                let config = {
                    method: method,
                    headers: encabezados,
                    mode: 'cors',
                    cache: 'no-cache',
                    body: data
                };

                fetch(action, config)
                    .then(respuesta => respuesta.json())
                    .then(respuesta => {
                        return alertas_ajax(respuesta);
                    });
            }
        });

    });

});


function alertas_ajax(alerta) {
    if (alerta.tipo == "simple") {

        Swal.fire({
            icon: alerta.icono,
            title: alerta.titulo,
            text: alerta.texto,
            confirmButtonText: 'Aceptar'
        });  

    } else if (alerta.tipo == "recargar") {

        Swal.fire({
            icon: alerta.icono,
            title: alerta.titulo,
            text: alerta.texto,
            confirmButtonText: 'Aceptar'
        }).then((result) => {
            if (result.isConfirmed) {
                location.reload();
            }
        });

    } else if (alerta.tipo == "limpiar") {

        Swal.fire({
            icon: alerta.icono,
            title: alerta.titulo,
            text: alerta.texto,
            confirmButtonText: 'Aceptar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.querySelector(".FormularioAjax").reset();
            }
        });

    } else if (alerta.tipo == "redireccionar") {
        Swal.fire({
            icon: alerta.icono,
            title: alerta.titulo,
            text: alerta.texto,
            confirmButtonText: 'Aceptar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = alerta.url;
            }
        });

    } else if (alerta.tipo == "recargar_directo") {
        location.reload();

    } else if (alerta.tipo == "Toast_Success") {
        var Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1000,
            timerProgressBar: false, // Muestra una barra de progreso
            didClose: () => {
                // Recargar la página cuando se cierra el mensaje
                location.reload();
            }
        });
    
        Toast.fire({
            icon: 'success',
            title: alerta.titulo
        });
    }

   
        
    
        /*
         var Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000
        });
      $('.swalDefaultSuccess').click(function() {
        Toast.fire({
          icon: 'success',
          title: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
        })
      });
      $('.swalDefaultInfo').click(function() {
        Toast.fire({
          icon: 'info',
          title: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
        })
      });
      $('.swalDefaultError').click(function() {
        Toast.fire({
          icon: 'error',
          title: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
        })
      });
      $('.swalDefaultWarning').click(function() {
        Toast.fire({
          icon: 'warning',
          title: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
        })
      });
      $('.swalDefaultQuestion').click(function() {
        Toast.fire({
          icon: 'question',
          title: 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr.'
        })
      });

      */
}

/* Boton cerrar sesion */
let btn_exit=document.getElementById("btn_exit");

btn_exit.addEventListener("click", function(e){

    e.preventDefault();
    
    Swal.fire({
        title: '¿Quiere salir del sistema?',
        text: "La sesión actual se cerrará y saldrá del sistema",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Si, salir',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let url=this.getAttribute("href");
            window.location.href=url;
        }
    });

});