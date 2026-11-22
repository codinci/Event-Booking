<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CalendarDays, CirclePlus, LayoutGrid, Ticket, Undo2 } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as bookingsIndex } from '@/routes/bookings';
import { index as refundsIndex } from '@/routes/admin/refunds';
import { create as createEvent, index as eventsIndex } from '@/routes/organizer/events';
import type { NavItem } from '@/types';

const page = usePage();

const myBookings: NavItem = { title: 'My bookings', href: bookingsIndex(), icon: Ticket };

const mainNavItems = computed<NavItem[]>(() => {
    const role = page.props.auth.user?.role;

    if (role === 'admin') {
        return [{ title: 'Refunds', href: refundsIndex(), icon: Undo2 }];
    }

    if (role === 'organizer') {
        return [
            { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
            { title: 'My events', href: eventsIndex(), icon: CalendarDays },
            { title: 'Create event', href: createEvent(), icon: CirclePlus },
            myBookings,
        ];
    }

    return [myBookings];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
