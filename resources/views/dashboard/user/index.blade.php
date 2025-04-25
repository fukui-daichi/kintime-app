<x-app-layout>
    <!-- Header -->
    <x-header :user="$user" />

    <!-- Sidebar -->
    <x-user.sidebar />

    <!-- MainContent -->
    <x-dashboard.user.main-content />
</x-app-layout>
