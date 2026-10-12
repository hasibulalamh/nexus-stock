document.addEventListener("DOMContentLoaded",function(){
    const manager=document.getElementById("manager");
    if(!manager) return;
    const emailField=document.getElementById("manager_email");
    const phoneField=document.getElementById("manager_phone");
    const csrf=document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const baseUrl=window.location.origin+'/admin/warehouse';

    manager.addEventListener("change",function(){
        const id=this.value;
        if(!id){ emailField.value=''; phoneField.value=''; return; }
        emailField.value='Loading...'; phoneField.value='Loading...';
        fetch(`${baseUrl}/get-manager/${id}`,{
            headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            credentials:'same-origin'
        })
        .then(r=>r.json())
        .then(data=>{ emailField.value=data.email||''; phoneField.value=data.phone||''; })
        .catch(err=>{ console.error(err); emailField.value='Error'; phoneField.value='Error'; });
    });
});


