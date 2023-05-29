(() => {
    'use strict'
    
    // Get all forms that need to be validated.
    const forms = document.querySelectorAll('.needs-validation')
  
    // Loop over them and prevent submission.
    Array.from(forms).forEach(form => {
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
  
        form.classList.add('was-validated')
      }, false)
    })
  })()