<?php

namespace App\Enums;

enum PermissionName: string
{
    case VIEW_PRODUCTS = 'view products';
    case CREATE_PRODUCTS = 'create products';
    case EDIT_PRODUCTS = 'edit products';
    case DELETE_PRODUCTS = 'delete products';
    case VIEW_ORDERS = 'view orders';
    case CREATE_ORDERS = 'create orders';
    case EDIT_ORDERS = 'edit orders';
    case CANCEL_ORDERS = 'cancel orders';
    case VIEW_USERS = 'view users';
    case CREATE_USERS = 'create users';
    case EDIT_USERS = 'edit users';
    case DELETE_USERS = 'delete users';
    case VIEW_ROLES = 'view roles';
    case CREATE_ROLES = 'create roles';
    case EDIT_ROLES = 'edit roles';
    case DELETE_ROLES = 'delete roles';
    case VIEW_ADMIN_DASHBOARD = 'view admin dashboard';
    case VIEW_CUSTOMER_DASHBOARD = 'view customer dashboard';

    public function label(): string
    {
        return match ($this) {
            self::VIEW_PRODUCTS => 'مشاهده محصولات',
            self::CREATE_PRODUCTS => 'ایجاد محصول',
            self::EDIT_PRODUCTS => 'ویرایش محصول',
            self::DELETE_PRODUCTS => 'حذف محصول',
            self::VIEW_ORDERS => 'مشاهده سفارش‌ها',
            self::CREATE_ORDERS => 'ایجاد سفارش',
            self::EDIT_ORDERS => 'ویرایش سفارش',
            self::CANCEL_ORDERS => 'لغو سفارش',
            self::VIEW_USERS => 'مشاهده کاربران',
            self::CREATE_USERS => 'ایجاد کاربر',
            self::EDIT_USERS => 'ویرایش کاربر',
            self::DELETE_USERS => 'حذف کاربر',
            self::VIEW_ROLES => 'مشاهده نقش‌ها',
            self::CREATE_ROLES => 'ایجاد نقش',
            self::EDIT_ROLES => 'ویرایش نقش',
            self::DELETE_ROLES => 'حذف نقش',
            self::VIEW_ADMIN_DASHBOARD => 'مشاهده داشبورد مدیریت',
            self::VIEW_CUSTOMER_DASHBOARD => 'مشاهده داشبورد مشتری',
        };
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom((string) $value)?->label() ?? (string) $value;
    }
}
