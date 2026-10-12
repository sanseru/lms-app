function toggleSearch() {
  document.getElementById('searchBox').classList.toggle('open');
  if (document.getElementById('searchBox').classList.contains('open')) document.querySelector('#searchBox input').focus();
}

function toggleMobileMenu() {
  document.querySelector('.main-menu').classList.toggle('open');
}

function showLoginMessage() {
  window.alert('The login page will be available soon.');
}

document.addEventListener('click', (event) => {
  const searchBox = document.getElementById('searchBox');
  const searchButton = document.querySelector('[aria-label="Search"]');
  if (searchBox.classList.contains('open') && !searchBox.contains(event.target) && !searchButton.contains(event.target)) searchBox.classList.remove('open');
});


// Show the signed-in user's name in the header instead of the Login button.
(function () {
  let user = null;
  try { user = JSON.parse(localStorage.getItem('alprogmatics-user')); } catch (e) {}
  if (!user || !user.name) return;
  const btn = document.querySelector('.login-button');
  if (!btn) return;
  const box = document.createElement('div');
  box.className = 'user-box';
  const name = document.createElement('span');
  name.className = 'user-name';
  name.textContent = user.name;
  const out = document.createElement('button');
  out.className = 'header-icon logout-btn';
  out.type = 'button';
  out.setAttribute('aria-label', 'Log out');
  out.title = 'Log out';
  out.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>';
  out.addEventListener('click', () => {
    localStorage.removeItem('alprogmatics-user');
    location.reload();
  });
  box.append(name, out);
  btn.replaceWith(box);
})();