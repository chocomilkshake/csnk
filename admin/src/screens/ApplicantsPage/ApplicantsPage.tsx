import { ApplicantsListSection } from "./sections/ApplicantsListSection/ApplicantsListSection";
import { FooterSection } from "./sections/FooterSection/FooterSection";
import { NavigationHeaderSection } from "./sections/NavigationHeaderSection";
import { SearchAndTitleSection } from "./sections/SearchAndTitleSection/SearchAndTitleSection";

export const ApplicantsPage = (): JSX.Element => {
  return (
    <div className="bg-[#f6f6f6] w-full min-h-screen flex flex-col">
      <NavigationHeaderSection />
      <SearchAndTitleSection />
      <ApplicantsListSection />
      <FooterSection />
    </div>
  );
};
