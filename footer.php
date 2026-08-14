</div></main></div>
<div class="admin-toast" id="adminToast" role="status" aria-live="polite"></div>
<script>
(function(){
  var sidebar=document.getElementById('adminSidebar');
  var overlay=document.getElementById('sidebarOverlay');
  var toggle=document.querySelector('.side-toggle');
  var themeToggle=document.getElementById('themeToggle');
  var toast=document.getElementById('adminToast');
  function closeSidebar(){ if(sidebar) sidebar.classList.remove('open'); }
  if(toggle&&sidebar) toggle.addEventListener('click',function(){ sidebar.classList.toggle('open'); });
  if(overlay) overlay.addEventListener('click',closeSidebar);
  document.addEventListener('keydown',function(e){
    if(e.key==='Escape') closeSidebar();
    if(e.key==='/' && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)){
      var s=document.getElementById('bookingSearch'); if(s){ e.preventDefault(); s.focus(); }
    }
  });

  var savedTheme=''; try{ savedTheme=localStorage.getItem('rr-admin-theme')||''; }catch(e){}
  if(savedTheme==='dark') document.body.classList.add('admin-dark');
  function syncTheme(){ if(themeToggle) themeToggle.textContent=document.body.classList.contains('admin-dark')?'Light mode':'Dark mode'; }
  syncTheme();
  if(themeToggle) themeToggle.addEventListener('click',function(){ document.body.classList.toggle('admin-dark'); try{ localStorage.setItem('rr-admin-theme',document.body.classList.contains('admin-dark')?'dark':'light'); }catch(e){} syncTheme(); });

  function showToast(text){ if(!toast) return; toast.textContent=text; toast.classList.add('show'); setTimeout(function(){toast.classList.remove('show');},1800); }
  Array.prototype.forEach.call(document.querySelectorAll('.copy-ref'),function(btn){ btn.addEventListener('click',function(){ var text=btn.getAttribute('data-copy')||''; if(navigator.clipboard&&navigator.clipboard.writeText){ navigator.clipboard.writeText(text).then(function(){showToast('Booking reference copied');}); } else { var ta=document.createElement('textarea');ta.value=text;document.body.appendChild(ta);ta.select();try{document.execCommand('copy');showToast('Booking reference copied');}catch(e){}document.body.removeChild(ta); } }); });
})();
</script>
</body></html>
