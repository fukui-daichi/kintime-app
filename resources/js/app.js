import './bootstrap';
import 'flowbite';
import Alpine from 'alpinejs';
import { toggleDarkMode } from './utils/theme.js';

window.toggleDarkMode = toggleDarkMode;

window.Alpine = Alpine;
