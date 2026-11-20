import { mount } from '@vue/test-utils';
import { beforeAll, describe, expect, it } from 'vitest';
import { nextTick } from 'vue';
import TimeRangeFields from './TimeRangeFields.vue';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import TimePickerSimple from '@/packages/ui/src/Input/TimePickerSimple.vue';
import { getLocalizedDayJs } from '@/packages/ui/src/utils/time';
import dayjs from 'dayjs';

const TZ = 'Europe/Belgrade';

beforeAll(() => {
    (window as unknown as { getTimezoneSetting: () => string }).getTimezoneSetting = () => TZ;
});

// Local (user timezone) ISO string for a wall-clock time, as the pickers produce
function local(wallClock: string) {
    return dayjs.tz(wallClock, TZ).format();
}

function mountRange(start: string, end: string) {
    const wrapper = mount(TimeRangeFields, {
        global: {
            stubs: { DatePicker: true, TimePickerSimple: true, DurationHumanInput: true },
        },
        props: {
            start,
            end,
            'onUpdate:start': (v: string) => wrapper.setProps({ start: v }),
            'onUpdate:end': (v: string) => wrapper.setProps({ end: v }),
        },
    });
    return wrapper;
}

function fmt(value: unknown) {
    return getLocalizedDayJs(value as string).format('YYYY-MM-DD HH:mm');
}

describe('TimeRangeFields', () => {
    it('moves the whole entry when the start date changes', async () => {
        const wrapper = mountRange(local('2026-11-18T09:15:00'), local('2026-11-18T11:00:00'));
        const [startDate] = wrapper.findAllComponents(DatePicker);
        // DatePicker swaps the date and keeps the time of day
        startDate!.vm.$emit('update:modelValue', local('2026-11-20T09:15:00'));
        await nextTick();
        await nextTick();

        expect(fmt(wrapper.props('start'))).toBe('2026-11-20 09:15');
        expect(fmt(wrapper.props('end'))).toBe('2026-11-20 11:00');
    });

    it('keeps the duration of an entry crossing midnight', async () => {
        const wrapper = mountRange(local('2026-11-18T22:00:00'), local('2026-11-19T01:00:00'));
        const [startDate] = wrapper.findAllComponents(DatePicker);
        startDate!.vm.$emit('update:modelValue', local('2026-11-25T22:00:00'));
        await nextTick();
        await nextTick();

        expect(fmt(wrapper.props('start'))).toBe('2026-11-25 22:00');
        expect(fmt(wrapper.props('end'))).toBe('2026-11-26 01:00');
    });

    it('moving the start date earlier keeps the duration too', async () => {
        const wrapper = mountRange(local('2026-11-18T09:15:00'), local('2026-11-18T11:00:00'));
        const [startDate] = wrapper.findAllComponents(DatePicker);
        startDate!.vm.$emit('update:modelValue', local('2026-11-10T09:15:00'));
        await nextTick();
        await nextTick();

        expect(fmt(wrapper.props('end'))).toBe('2026-11-10 11:00');
    });

    it('changing only the end date keeps the start and the end time', async () => {
        const wrapper = mountRange(local('2026-11-18T09:15:00'), local('2026-11-18T11:00:00'));
        const [, endDate] = wrapper.findAllComponents(DatePicker);
        endDate!.vm.$emit('update:modelValue', local('2026-11-19T11:00:00'));
        await nextTick();
        await nextTick();

        expect(fmt(wrapper.props('start'))).toBe('2026-11-18 09:15');
        expect(fmt(wrapper.props('end'))).toBe('2026-11-19 11:00');
    });

    it('moving the start time past the end drags the end along with the old duration', async () => {
        const wrapper = mountRange(local('2026-11-18T09:15:00'), local('2026-11-18T11:00:00'));
        const [startTime] = wrapper.findAllComponents(TimePickerSimple);
        startTime!.vm.$emit('update:modelValue', local('2026-11-18T12:00:00'));
        await nextTick();
        await nextTick();

        expect(fmt(wrapper.props('start'))).toBe('2026-11-18 12:00');
        expect(fmt(wrapper.props('end'))).toBe('2026-11-18 13:45');
    });
});
