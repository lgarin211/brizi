<div class="card">
    <div class="card-body text-center">
        <h4 class="card-title">Update Cache</h4>
        <button id="update-cache-btn" class="btn btn-primary">Update Cache (Push All to Firebase)</button>
        <div id="update-cache-result" style="margin-top:10px;"></div>
    </div>
</div>
<script>
document.getElementById('update-cache-btn').onclick = function() {
    var btn = this;
    btn.disabled = true;
    btn.innerText = 'Updating...';
    fetch('/api/firebase/push/all', {method: 'POST', headers: {'Accept': 'application/json'}})
        .then(r => r.json())
        .then(data => {
            document.getElementById('update-cache-result').innerHTML = '<span class="badge badge-success">Success!</span>';
            btn.innerText = 'Update Cache';
            btn.disabled = false;
        })
        .catch(e => {
            document.getElementById('update-cache-result').innerHTML = '<span class="badge badge-danger">Failed!</span>';
            btn.innerText = 'Update Cache';
            btn.disabled = false;
        });
};
</script>
