<x-app-layout>
    <!-- Header -->
    <x-header :user="$user" />

    <!-- Sidebar -->
    <x-admin.sidebar />

    <!-- MainContent -->
    <x-dashboard.admin.main-content />
</x-app-layout>
