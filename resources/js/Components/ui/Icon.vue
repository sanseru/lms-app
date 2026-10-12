<script setup>
import { computed } from 'vue';
import {
    AlertTriangle, ArrowLeft, ArrowRight, BadgeCheck, Ban, Bell, BookOpen, CalendarDays, Check, ClipboardCheck, Flag, HandCoins, Ticket, Printer, ChevronDown,
    ChevronLeft, ChevronRight, CircleDollarSign, Clock, Download, Eye, FileText, GraduationCap, Inbox,
    LayoutDashboard, LogOut, Mail, Menu, Pencil, Phone, Plus, Presentation, Receipt, School, Search,
    Sparkles, Trash2, Upload, User, UserPlus, Users, Video, Wallet, X,
    ListChecks, FilePen, ArrowUp, ArrowDown, Link2, Paperclip, CircleCheck, Lock, Play, Trophy, RotateCcw, Target, Code, FlaskConical, Rocket,
    MessageCircle, MessageCircleQuestion, Pin, ThumbsUp, Image, EyeOff, Hand, Send,
    SquareKanban, GripVertical, ExternalLink, MessageSquareText, Award, ShieldCheck, ShieldX, Maximize2, Minimize2, Compass, Star, Medal, Hammer, Layers,
} from 'lucide-vue-next';

const icons = {
    'alert-triangle': AlertTriangle, 'arrow-left': ArrowLeft, 'arrow-right': ArrowRight, 'badge-check': BadgeCheck,
    ban: Ban, bell: Bell, 'book-open': BookOpen, 'calendar-days': CalendarDays, check: Check, 'clipboard-check': ClipboardCheck, flag: Flag, 'hand-coins': HandCoins, ticket: Ticket, printer: Printer, 'chevron-down': ChevronDown,
    'chevron-left': ChevronLeft, 'chevron-right': ChevronRight, money: CircleDollarSign, clock: Clock, download: Download,
    eye: Eye, 'file-text': FileText, 'graduation-cap': GraduationCap, inbox: Inbox, 'layout-dashboard': LayoutDashboard,
    'log-out': LogOut, mail: Mail, menu: Menu, pencil: Pencil, phone: Phone, plus: Plus, presentation: Presentation,
    receipt: Receipt, school: School, search: Search, sparkles: Sparkles, trash: Trash2, upload: Upload, user: User,
    'user-plus': UserPlus, users: Users, video: Video, wallet: Wallet, x: X,
    'list-checks': ListChecks, 'file-pen': FilePen, 'arrow-up': ArrowUp, 'arrow-down': ArrowDown, link: Link2, paperclip: Paperclip,
    'circle-check': CircleCheck, lock: Lock, play: Play, trophy: Trophy, 'rotate-ccw': RotateCcw, target: Target, code: Code, flask: FlaskConical, rocket: Rocket,
    'message-circle': MessageCircle, 'message-question': MessageCircleQuestion, pin: Pin, 'thumbs-up': ThumbsUp, image: Image, 'eye-off': EyeOff, hand: Hand, send: Send,
    kanban: SquareKanban, grip: GripVertical, 'external-link': ExternalLink, 'message-text': MessageSquareText, award: Award, 'shield-check': ShieldCheck, 'shield-x': ShieldX,
    maximize: Maximize2, minimize: Minimize2, compass: Compass, star: Star, medal: Medal, hammer: Hammer, layers: Layers,
};

const props = defineProps({
    name: { type: String, required: true },
    size: { type: [Number, String], default: 18 },
});

const component = computed(() => icons[props.name] ?? Sparkles);
</script>

<template>
    <component :is="component" :size="size" :stroke-width="2" aria-hidden="true" />
</template>
