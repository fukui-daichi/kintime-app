<x-app-layout>
    <!-- Header -->
    <x-header :user="$user" />

    <!-- Sidebar -->
    <x-user.sidebar />

    <!-- MainContent -->
    <x-dashboard.user.main-content
        :user="$user"
        :timecardButtonStatus="$timecardButtonStatus"
        :timecard="$timecard"
        :currentDate="$currentDate" />
</x-app-layout>
