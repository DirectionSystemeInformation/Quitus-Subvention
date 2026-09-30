@props(['name' => 'file'])
<svg {{ $attributes->class(['ui-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($name)
        @case('grid')<rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/>@break
        @case('users')<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2m20 0v-2a4 4 0 0 0-3-3.87"/><circle cx="9" cy="7" r="4"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>@break
        @case('check')<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m22 4-10 10-3-3"/>@break
        @case('calendar')<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18m-13 5h2m4 0h2"/>@break
        @case('list')<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>@break
        @case('scales')<path d="M12 3v18M5 7l-3 6h6zm14 0-3 6h6zM5 7l7-2 7 2M8 21h8"/>@break
        @case('clock')<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>@break
        @case('arrow')<path d="M5 12h14m-6-6 6 6-6 6"/>@break
        @case('search')<circle cx="10.5" cy="10.5" r="7.5"/><path d="m16 16 5 5"/>@break
        @case('edit')<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4z"/>@break
        @case('trash')<path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m5 5v6m4-6v6"/>@break
        @case('plus')<path d="M12 5v14M5 12h14"/>@break
        @case('download')<path d="M12 3v12m-5-5 5 5 5-5M4 19h16"/>@break
        @case('eye')<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>@break
        @case('lock')<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>@break
        @case('undo')<path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-1"/>@break
        @case('logout')<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4m7 14 5-5-5-5m5 5H9"/>@break
        @default<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M8 13h8M8 17h5"/>
    @endswitch
</svg>
