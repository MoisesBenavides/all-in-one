function idioma(idioma) {
    fetch('../traducciones.json')
    .then(response => response.json())
    .then(traducciones => {
      const elementos = document.querySelectorAll('[traducir]');
      elementos.forEach(elemento => {
        const clave = elemento.getAttribute('traducir');
        const idi = idioma; 
        const traduccion = traducciones[idi][clave];
        elemento.textContent = traduccion;
      });
    })
    .catch(error => {
      console.error('Error al cargar el archivo:', error);
    });
  }
  idioma("it");