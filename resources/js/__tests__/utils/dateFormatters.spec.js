import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { formatDate, getDaysLeft, getDaysLeftClass, formatDaysLeft } from '@/Utils/dateFormatters';

describe('dateFormatters', () => {
    describe('formatDate', () => {
        it('returns "DD Mon YYYY" format for a valid YYYY-MM-DD date', () => {
            expect(formatDate('2025-01-15')).toBe('15 Jan 2025');
        });

        it('returns "DD Mon YYYY" format for various months', () => {
            expect(formatDate('2024-03-05')).toBe('5 Mar 2024');
            expect(formatDate('2023-12-25')).toBe('25 Dec 2023');
            expect(formatDate('2025-07-01')).toBe('1 Jul 2025');
        });

        it('returns "-" for null or empty input', () => {
            expect(formatDate(null)).toBe('-');
            expect(formatDate('')).toBe('-');
            expect(formatDate(undefined)).toBe('-');
        });

        it('returns "-" for invalid date strings', () => {
            expect(formatDate('not-a-date')).toBe('-');
        });

        it('handles ISO 8601 date strings', () => {
            expect(formatDate('2025-06-15T10:30:00.000Z')).toBe('15 Jun 2025');
        });
    });

    describe('getDaysLeft', () => {
        let dateSpy;

        beforeEach(() => {
            // Mock "today" as 2025-01-15
            vi.useFakeTimers();
            vi.setSystemTime(new Date(2025, 0, 15)); // Jan 15, 2025
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('returns "Due today" when date is today', () => {
            expect(getDaysLeft('2025-01-15')).toBe('Due today');
        });

        it('returns "Due tomorrow" when date is tomorrow', () => {
            expect(getDaysLeft('2025-01-16')).toBe('Due tomorrow');
        });

        it('returns "X days left" for future dates', () => {
            expect(getDaysLeft('2025-01-20')).toBe('5 days left');
            expect(getDaysLeft('2025-01-17')).toBe('2 days left');
        });

        it('returns "X days overdue" for past dates', () => {
            expect(getDaysLeft('2025-01-14')).toBe('1 days overdue');
            expect(getDaysLeft('2025-01-10')).toBe('5 days overdue');
        });

        it('returns empty string for null/empty input', () => {
            expect(getDaysLeft(null)).toBe('');
            expect(getDaysLeft('')).toBe('');
        });
    });

    describe('getDaysLeftClass', () => {
        beforeEach(() => {
            vi.useFakeTimers();
            vi.setSystemTime(new Date(2025, 0, 15)); // Jan 15, 2025
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('returns text-red-700 font-bold for past dates', () => {
            expect(getDaysLeftClass('2025-01-14')).toBe('text-red-700 font-bold');
            expect(getDaysLeftClass('2025-01-10')).toBe('text-red-700 font-bold');
        });

        it('returns text-red-500 font-bold for today', () => {
            expect(getDaysLeftClass('2025-01-15')).toBe('text-red-500 font-bold');
        });

        it('returns text-orange-400 font-bold for 1-2 days', () => {
            expect(getDaysLeftClass('2025-01-16')).toBe('text-orange-400 font-bold');
            expect(getDaysLeftClass('2025-01-17')).toBe('text-orange-400 font-bold');
        });

        it('returns text-amber-400 font-bold for 3-5 days', () => {
            expect(getDaysLeftClass('2025-01-18')).toBe('text-amber-400 font-bold');
            expect(getDaysLeftClass('2025-01-20')).toBe('text-amber-400 font-bold');
        });

        it('returns text-emerald-400 font-medium for >5 days', () => {
            expect(getDaysLeftClass('2025-01-21')).toBe('text-emerald-400 font-medium');
            expect(getDaysLeftClass('2025-02-15')).toBe('text-emerald-400 font-medium');
        });

        it('returns text-slate-500 for null/empty input', () => {
            expect(getDaysLeftClass(null)).toBe('text-slate-500');
            expect(getDaysLeftClass('')).toBe('text-slate-500');
        });
    });

    describe('formatDaysLeft', () => {
        beforeEach(() => {
            vi.useFakeTimers();
            vi.setSystemTime(new Date(2025, 0, 15)); // Jan 15, 2025
        });

        afterEach(() => {
            vi.useRealTimers();
        });

        it('returns combined format with date and days left', () => {
            expect(formatDaysLeft('2025-01-20')).toBe('20 Jan 2025 (5 days left)');
        });

        it('returns combined format for today', () => {
            expect(formatDaysLeft('2025-01-15')).toBe('15 Jan 2025 (Due today)');
        });

        it('returns combined format for overdue', () => {
            expect(formatDaysLeft('2025-01-10')).toBe('10 Jan 2025 (5 days overdue)');
        });

        it('returns just the formatted date for null input', () => {
            expect(formatDaysLeft(null)).toBe('-');
        });
    });
});
