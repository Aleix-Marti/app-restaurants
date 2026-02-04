/**
 * Selector de tema: clar, fosc o sistema (radio buttons amb icones).
 * Guarda la preferència a localStorage i aplica data-theme a <html>.
 */
(function() {
	'use strict';

	var STORAGE_KEY = 'amc-theme';
	var name = 'theme-switcher';
	var radios = document.querySelectorAll('input[name="' + name + '"]');
	var container = document.querySelector('.theme-switcher');

	function getResolvedTheme(choice) {
		if (choice === 'light' || choice === 'dark') {
			return choice;
		}
		return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
	}

	function applyTheme(resolved) {
		document.documentElement.setAttribute('data-theme', resolved);
	}

	function syncRadios() {
		var stored = localStorage.getItem(STORAGE_KEY) || 'system';
		radios.forEach(function(radio) {
			var label = radio.closest('.theme-switcher__option');
			if (radio.value === stored) {
				radio.checked = true;
				if (label) label.classList.add('theme-switcher__option--checked');
			} else {
				radio.checked = false;
				if (label) label.classList.remove('theme-switcher__option--checked');
			}
		});
	}

	function updateCheckedState(checkedRadio) {
		var options = container ? container.querySelectorAll('.theme-switcher__option') : [];
		options.forEach(function(opt) {
			opt.classList.toggle('theme-switcher__option--checked', opt.contains(checkedRadio));
		});
	}

	function handleChange(e) {
		var radio = e.target;
		if (!radio || radio.name !== name) return;
		var choice = radio.value;
		localStorage.setItem(STORAGE_KEY, choice);
		var resolved = getResolvedTheme(choice);
		applyTheme(resolved);
		updateCheckedState(radio);
	}

	function handleSystemChange(e) {
		var stored = localStorage.getItem(STORAGE_KEY);
		if (stored !== 'system') return;
		applyTheme(e.matches ? 'dark' : 'light');
	}

	// Inicialitzar radio marcat segons localStorage (per defecte: system)
	syncRadios();

	radios.forEach(function(radio) {
		radio.addEventListener('change', handleChange);
	});

	// Quan l'usuari tria "Sistema", reagir als canvis de preferència del SO
	window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', handleSystemChange);
})();
