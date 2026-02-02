import {
  NavigationMenu,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
} from "../../../../components/ui/navigation-menu";

const navigationItems = [
  { label: "Home", isActive: true },
  { label: "Applicants", isActive: false },
  { label: "About", isActive: false },
  { label: "Contact", isActive: false },
];

export const NavigationHeaderSection = (): JSX.Element => {
  return (
    <header className="relative w-full bg-transparent">
      <div className="relative w-full">
        <div className="w-full flex items-center justify-center bg-white rounded-[0px_0px_16px_16px] overflow-hidden shadow-[0px_8px_10px_-6px_#0000001a,0px_20px_25px_-5px_#0000001a] py-6">
          <img className="h-[148.85px] w-[417px] object-cover" alt="Logo" />
        </div>

        <div className="flex items-center justify-center mt-6">
          <NavigationMenu className="bg-[#e64f52] rounded-2xl border border-solid border-[#505050] shadow-[0px_8px_10px_-6px_#0000001a,0px_20px_25px_-5px_#0000001a] px-[25px] py-2">
            <NavigationMenuList className="flex gap-[35px]">
              {navigationItems.map((item, index) => (
                <NavigationMenuItem key={index}>
                  <NavigationMenuLink
                    className={`flex items-center justify-center h-[19px] [font-family:'Inter',Helvetica] font-medium text-white text-sm tracking-[-0.07px] leading-[18.2px] whitespace-nowrap ${
                      item.isActive ? "underline" : ""
                    }`}
                  >
                    {item.label}
                  </NavigationMenuLink>
                </NavigationMenuItem>
              ))}
            </NavigationMenuList>
          </NavigationMenu>
        </div>
      </div>
    </header>
  );
};
