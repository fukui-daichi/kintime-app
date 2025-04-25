<x-app-layout>
    <!-- Header -->
    <x-header :user="$user" />

    <!-- Sidebar -->
    <x-manager.sidebar />

    <!-- MainContent -->
    <x-dashboard.manager.main-content />
</x-app-layout>
